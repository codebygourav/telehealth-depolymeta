"use client";

import type { VoiceTranscriptionResult } from "@/api/voice-transcription";
import { useVoiceTranscription } from "@/queries/useVoiceTranscription";
import { Loader2, Mic } from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";

type BrowserSpeechRecognitionAlternative = {
  transcript: string;
};

type BrowserSpeechRecognitionResult = {
  isFinal: boolean;
  0: BrowserSpeechRecognitionAlternative;
};

type BrowserSpeechRecognitionEvent = {
  resultIndex: number;
  results: ArrayLike<BrowserSpeechRecognitionResult>;
};

type BrowserSpeechRecognition = {
  continuous: boolean;
  interimResults: boolean;
  lang: string;
  maxAlternatives: number;
  onresult: ((event: BrowserSpeechRecognitionEvent) => void) | null;
  onerror: (() => void) | null;
  onend: (() => void) | null;
  start: () => void;
  stop: () => void;
  abort: () => void;
};

type BrowserSpeechRecognitionConstructor = new () => BrowserSpeechRecognition;

interface DeepgramVoiceRecorderProps {
  appointmentId: string;
  selectedLanguage: string;
  transcriptValue: string;
  onTranscriptChange: (value: string) => void;
  onResult: (result: VoiceTranscriptionResult) => void;
  onRecordingChange: (value: boolean) => void;
  onProcessingChange: (value: boolean) => void;
  onErrorChange: (value: string | null) => void;
}

export default function DeepgramVoiceRecorder({
  appointmentId,
  selectedLanguage,
  transcriptValue,
  onTranscriptChange,
  onResult,
  onRecordingChange,
  onProcessingChange,
  onErrorChange,
}: DeepgramVoiceRecorderProps) {
  const [isRecording, setIsRecording] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [confidence, setConfidence] = useState<number | null>(null);
  const [duration, setDuration] = useState<number | null>(null);
  const [creditsUsed, setCreditsUsed] = useState<number | null>(null);

  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const recognitionRef = useRef<BrowserSpeechRecognition | null>(null);
  const chunksRef = useRef<Blob[]>([]);
  const baseTranscriptRef = useRef("");
  const liveFinalTranscriptRef = useRef("");

  const transcribeMutation = useVoiceTranscription(appointmentId);

  useEffect(() => {
    return () => {
      recognitionRef.current?.abort();
    };
  }, []);

  const startSpeechPreview = useCallback(() => {
    const SpeechRecognitionApi = getSpeechRecognitionApi();

    if (!SpeechRecognitionApi) {
      return;
    }

    recognitionRef.current?.abort();

    const recognition = new SpeechRecognitionApi();
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = mapLocaleToBrowserSpeechLanguage(selectedLanguage);
    recognition.maxAlternatives = 1;

    recognition.onresult = (event) => {
      let finalTranscript = liveFinalTranscriptRef.current;
      let interimTranscript = "";

      for (
        let index = event.resultIndex;
        index < event.results.length;
        index += 1
      ) {
        const result = event.results[index];
        const nextPart = result?.[0]?.transcript?.trim();

        if (!nextPart) {
          continue;
        }

        if (result.isFinal) {
          finalTranscript = combineTranscript(finalTranscript, nextPart);
        } else {
          interimTranscript = combineTranscript(interimTranscript, nextPart);
        }
      }

      liveFinalTranscriptRef.current = cleanDuplicateWords(finalTranscript);

      onTranscriptChange(
        cleanDuplicateWords(
          combineTranscript(
            baseTranscriptRef.current,
            liveFinalTranscriptRef.current,
            interimTranscript,
          ),
        ),
      );
    };

    recognition.onerror = () => {
      // Live preview is optional. Keep Deepgram recording active.
    };

    recognition.onend = () => {
      if (recognitionRef.current === recognition) {
        recognitionRef.current = null;
      }
    };

    recognitionRef.current = recognition;

    try {
      recognition.start();
    } catch {
      recognitionRef.current = null;
    }
  }, [onTranscriptChange, selectedLanguage]);

  const startRecording = useCallback(async () => {
    onErrorChange(null);
    setConfidence(null);
    setDuration(null);
    setCreditsUsed(null);
    baseTranscriptRef.current = (transcriptValue || "").trim();
    liveFinalTranscriptRef.current = "";

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const recorder = new MediaRecorder(stream, {
        mimeType: MediaRecorder.isTypeSupported("audio/webm;codecs=opus")
          ? "audio/webm;codecs=opus"
          : "audio/webm",
      });

      chunksRef.current = [];

      recorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          chunksRef.current.push(event.data);
        }
      };

      recorder.onstop = async () => {
        stream.getTracks().forEach((track) => track.stop());
        recognitionRef.current?.stop();
        recognitionRef.current = null;

        const audioBlob = new Blob(chunksRef.current, {
          type: recorder.mimeType,
        });

        setIsRecording(false);
        onRecordingChange(false);
        setIsProcessing(true);
        onProcessingChange(true);

        try {
          const result = await transcribeMutation.mutateAsync({
            audioBlob,
            language: mapLocaleToDeepgramLanguage(selectedLanguage),
          });

          const mergedTranscript = cleanDuplicateWords(
            combineTranscript(
              baseTranscriptRef.current,
              result.transcript || liveFinalTranscriptRef.current,
            ),
          );

          setConfidence(result.confidence ?? null);
          setDuration(result.duration_seconds ?? null);
          setCreditsUsed(
            typeof result.credits_used === "number"
              ? result.credits_used
              : null,
          );
          onTranscriptChange(mergedTranscript);
          onResult({
            ...result,
            transcript: mergedTranscript,
          });
          onErrorChange(null);
        } catch (error: unknown) {
          const message =
            (
              error as {
                response?: { data?: { errors?: { message?: string } } };
              }
            )?.response?.data?.errors?.message ??
            (error instanceof Error
              ? error.message
              : "Transcription failed. Please try again.");
          onErrorChange(message);
        } finally {
          setIsProcessing(false);
          onProcessingChange(false);
        }
      };

      recorder.start();
      mediaRecorderRef.current = recorder;
      setIsRecording(true);
      onRecordingChange(true);
      startSpeechPreview();
    } catch {
      onErrorChange(
        "Microphone access denied. Please allow microphone access.",
      );
    }
  }, [
    onErrorChange,
    onProcessingChange,
    onRecordingChange,
    onResult,
    onTranscriptChange,
    selectedLanguage,
    startSpeechPreview,
    transcriptValue,
    transcribeMutation,
  ]);

  const stopRecording = useCallback(() => {
    mediaRecorderRef.current?.stop();
  }, []);

  return (
    <div className="flex flex-wrap items-center gap-3">
      <button
        type="button"
        onClick={isRecording ? stopRecording : startRecording}
        disabled={isProcessing}
        className={[
          "flex h-16 w-16 items-center justify-center rounded-full text-white shadow-lg transition-all active:scale-95",
          isRecording
            ? "bg-red-500 hover:bg-red-600 animate-pulse"
            : isProcessing
              ? "bg-blue-200 cursor-not-allowed"
              : "bg-blue-600 hover:bg-blue-700",
        ].join(" ")}
        title={isRecording ? "Stop recording" : "Start recording"}
      >
        {isProcessing ? (
          <Loader2 className="h-6 w-6 animate-spin" />
        ) : isRecording ? (
          <Loader2 className="h-6 w-6 animate-spin" />
        ) : (
          <Mic className="h-6 w-6" />
        )}
      </button>

      <div className="min-w-0 flex-1 space-y-1">
        <p className="text-sm font-bold text-gray-800">
          {isRecording
            ? "Recording current step..."
            : isProcessing
              ? "Transcribing current step..."
              : "Deepgram Cloud AI"}
        </p>
        <p className="text-xs text-gray-500">
          {isRecording
            ? "Speak only this step, then tap again to stop."
            : isProcessing
              ? "Deepgram is checking the speech and filling this step."
              : "Tap the mic and speak only the current step."}
        </p>

        {(confidence !== null || duration !== null || creditsUsed !== null) && (
          <div className="flex flex-wrap items-center gap-2 pt-1">
            {confidence !== null && (
              <span
                className={[
                  "rounded-full px-2 py-0.5 text-[10px] font-bold",
                  confidence >= 85
                    ? "bg-green-100 text-green-700"
                    : confidence >= 60
                      ? "bg-yellow-100 text-yellow-700"
                      : "bg-red-100 text-red-600",
                ].join(" ")}
              >
                {confidence}% accuracy
              </span>
            )}
            {duration !== null && duration > 0 && (
              <span className="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-600">
                {duration.toFixed(1)}s
              </span>
            )}
            {typeof creditsUsed === "number" &&
              Number.isFinite(creditsUsed) && (
                <span className="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] text-blue-600">
                  ~${creditsUsed.toFixed(5)}
                </span>
              )}
          </div>
        )}
      </div>
    </div>
  );
}

function getSpeechRecognitionApi(): BrowserSpeechRecognitionConstructor | null {
  if (typeof window === "undefined") {
    return null;
  }

  const speechWindow = window as Window & {
    SpeechRecognition?: BrowserSpeechRecognitionConstructor;
    webkitSpeechRecognition?: BrowserSpeechRecognitionConstructor;
  };

  return (
    speechWindow.SpeechRecognition ||
    speechWindow.webkitSpeechRecognition ||
    null
  );
}

function mapLocaleToDeepgramLanguage(locale: string): string {
  const normalized = String(locale || "")
    .trim()
    .toLowerCase();

  if (!normalized || normalized === "auto") {
    return "multi";
  }

  const [language] = normalized.split("-");
  return language || "en";
}

function mapLocaleToBrowserSpeechLanguage(locale: string): string {
  const normalized = String(locale || "").trim();
  return normalized && normalized.toLowerCase() !== "auto"
    ? normalized
    : "en-IN";
}

function combineTranscript(...parts: Array<string | null | undefined>) {
  return parts
    .map((part) => (part || "").trim())
    .filter(Boolean)
    .join(" ")
    .replace(/\s+/g, " ")
    .trim();
}

function cleanDuplicateWords(text: string): string {
  if (!text) return "";

  const words = text.split(/\s+/);
  const result: string[] = [];

  for (let index = 0; index < words.length; index += 1) {
    const word = words[index];
    if (index > 0 && word.toLowerCase() === words[index - 1].toLowerCase()) {
      continue;
    }
    result.push(word);
  }

  return result.join(" ");
}
