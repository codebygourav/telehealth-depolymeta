"use client";

/**
 * DeepgramVoiceRecorder
 * Reusable component — records audio via MediaRecorder, sends to backend Deepgram endpoint,
 * returns the transcript + parsed prescription fields to the parent via onResult().
 *
 * Usage:
 *   <DeepgramVoiceRecorder
 *     appointmentId={id}
 *     onResult={(result) => { ... }}
 *     language="en"
 *   />
 */

import type { VoiceTranscriptionResult } from "@/api/voice-transcription";
import { Button } from "@/components/ui/button";
import { useVoiceTranscription } from "@/queries/useVoiceTranscription";
import { AlertCircle, CheckCircle2, Loader2, Mic, MicOff } from "lucide-react";
import { useCallback, useRef, useState } from "react";

type RecorderState = "idle" | "recording" | "processing" | "done" | "error";

type Language = { label: string; value: string };

const LANGUAGES: Language[] = [
  { label: "English", value: "en" },
  { label: "Hindi", value: "hi" },
  { label: "Punjabi", value: "pa" },
];

interface DeepgramVoiceRecorderProps {
  appointmentId: string;
  onResult: (result: VoiceTranscriptionResult) => void;
  defaultLanguage?: string;
}

export default function DeepgramVoiceRecorder({
  appointmentId,
  onResult,
  defaultLanguage = "en",
}: DeepgramVoiceRecorderProps) {
  const [state, setState] = useState<RecorderState>("idle");
  const [language, setLanguage] = useState(defaultLanguage);
  const [transcript, setTranscript] = useState("");
  const [duration, setDuration] = useState(0);
  const [creditsUsed, setCreditsUsed] = useState<number | null>(null);
  const [confidence, setConfidence] = useState<number | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [warnings, setWarnings] = useState<string[]>([]);

  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const chunksRef = useRef<Blob[]>([]);
  const startTimeRef = useRef<number>(0);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const [elapsed, setElapsed] = useState(0);

  const transcribeMutation = useVoiceTranscription(appointmentId);

  const startRecording = useCallback(async () => {
    setErrorMsg(null);
    setTranscript("");
    setWarnings([]);
    setCreditsUsed(null);
    setConfidence(null);
    setElapsed(0);

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const recorder = new MediaRecorder(stream, {
        mimeType: MediaRecorder.isTypeSupported("audio/webm;codecs=opus")
          ? "audio/webm;codecs=opus"
          : "audio/webm",
      });

      chunksRef.current = [];
      recorder.ondataavailable = (e) => {
        if (e.data.size > 0) chunksRef.current.push(e.data);
      };

      recorder.onstop = async () => {
        stream.getTracks().forEach((t) => t.stop());
        const audioBlob = new Blob(chunksRef.current, {
          type: recorder.mimeType,
        });
        setDuration(Math.round((Date.now() - startTimeRef.current) / 1000));
        setState("processing");

        try {
          const result = await transcribeMutation.mutateAsync({
            audioBlob,
            language,
          });
          setTranscript(result.transcript);
          setCreditsUsed(result.credits_used);
          setConfidence(result.confidence);
          setWarnings(result.warnings ?? []);
          setState("done");
          onResult(result);
        } catch (err: unknown) {
          const msg =
            (err as { response?: { data?: { errors?: { message?: string } } } })
              ?.response?.data?.errors?.message ??
            (err instanceof Error ? err.message : "Transcription failed.");
          setErrorMsg(msg);
          setState("error");
        }
      };

      recorder.start();
      mediaRecorderRef.current = recorder;
      startTimeRef.current = Date.now();
      setState("recording");

      timerRef.current = setInterval(() => {
        setElapsed(Math.round((Date.now() - startTimeRef.current) / 1000));
      }, 1000);
    } catch {
      setErrorMsg("Microphone access denied. Please allow microphone access.");
      setState("error");
    }
  }, [language, transcribeMutation, onResult]);

  const stopRecording = useCallback(() => {
    if (timerRef.current) clearInterval(timerRef.current);
    mediaRecorderRef.current?.stop();
    setState("processing");
  }, []);

  const reset = useCallback(() => {
    setState("idle");
    setTranscript("");
    setCreditsUsed(null);
    setConfidence(null);
    setWarnings([]);
    setErrorMsg(null);
    setElapsed(0);
    setDuration(0);
  }, []);

  return (
    <div className="rounded-2xl border border-blue-200 bg-gradient-to-b from-blue-50 to-white p-5 space-y-4">
      {/* Language selector */}
      <div className="flex gap-2 flex-wrap">
        {LANGUAGES.map((lang) => (
          <button
            key={lang.value}
            type="button"
            onClick={() => setLanguage(lang.value)}
            disabled={state === "recording" || state === "processing"}
            className={[
              "rounded-full px-4 py-1.5 text-sm font-bold border transition-colors",
              language === lang.value
                ? "bg-blue-600 border-blue-600 text-white"
                : "bg-white border-gray-200 text-gray-600 hover:border-blue-400",
              (state === "recording" || state === "processing")
                ? "opacity-50 cursor-not-allowed"
                : "",
            ].join(" ")}
          >
            {lang.label}
          </button>
        ))}
      </div>

      {/* Main mic button area */}
      <div className="flex gap-4 items-center">
        {state === "idle" && (
          <button
            type="button"
            onClick={startRecording}
            className="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-lg hover:bg-blue-700 active:scale-95 transition-all"
            title="Start recording"
          >
            <Mic size={26} />
          </button>
        )}

        {state === "recording" && (
          <button
            type="button"
            onClick={stopRecording}
            className="w-16 h-16 rounded-full bg-red-500 text-white flex items-center justify-center shadow-lg hover:bg-red-600 active:scale-95 transition-all animate-pulse"
            title="Stop recording"
          >
            <MicOff size={26} />
          </button>
        )}

        {state === "processing" && (
          <div className="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
            <Loader2 size={26} className="text-blue-600 animate-spin" />
          </div>
        )}

        {state === "done" && (
          <div className="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
            <CheckCircle2 size={26} className="text-green-600" />
          </div>
        )}

        {state === "error" && (
          <div className="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
            <AlertCircle size={26} className="text-red-500" />
          </div>
        )}

        <div className="flex-1">
          {state === "idle" && (
            <>
              <p className="font-bold text-gray-800">Voice AI Prescription</p>
              <p className="text-sm text-gray-500">
                Tap the mic and speak your prescription. AI will extract the
                medicine fields automatically.
              </p>
            </>
          )}

          {state === "recording" && (
            <>
              <p className="font-bold text-red-600 flex items-center gap-2">
                <span className="inline-block w-2 h-2 rounded-full bg-red-500 animate-ping" />
                Recording… {elapsed}s
              </p>
              <p className="text-sm text-gray-500">
                Speak clearly. Tap the button again to stop.
              </p>
            </>
          )}

          {state === "processing" && (
            <>
              <p className="font-bold text-blue-600">Transcribing…</p>
              <p className="text-sm text-gray-500">
                Deepgram AI is processing your voice. This takes 2–5 seconds.
              </p>
            </>
          )}

          {state === "done" && (
            <>
              <p className="font-bold text-green-700">Transcript ready</p>
              <div className="flex items-center gap-3 mt-1 flex-wrap">
                {duration > 0 && (
                  <span className="text-xs bg-gray-100 text-gray-600 rounded-full px-2 py-0.5">
                    {duration}s
                  </span>
                )}
                {confidence !== null && (
                  <span
                    className={[
                      "text-xs rounded-full px-2 py-0.5 font-bold",
                      confidence >= 85
                        ? "bg-green-100 text-green-700"
                        : confidence >= 60
                          ? "bg-yellow-100 text-yellow-700"
                          : "bg-red-100 text-red-600",
                    ].join(" ")}
                  >
                    {confidence}% confidence
                  </span>
                )}
                {creditsUsed !== null && (
                  <span className="text-xs bg-blue-50 text-blue-600 rounded-full px-2 py-0.5">
                    ~${creditsUsed.toFixed(5)} used
                  </span>
                )}
              </div>
            </>
          )}

          {state === "error" && (
            <p className="text-sm text-red-600 font-medium">
              {errorMsg ?? "Something went wrong."}
            </p>
          )}
        </div>
      </div>

      {/* Transcript display */}
      {state === "done" && transcript && (
        <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-gray-800 leading-relaxed">
          <p className="text-xs font-bold text-blue-700 mb-2 uppercase tracking-wide">
            Transcript
          </p>
          <p>{transcript}</p>
        </div>
      )}

      {/* Warnings */}
      {warnings.length > 0 && (
        <div className="rounded-xl border border-orange-200 bg-orange-50 p-3 space-y-1">
          {warnings.map((w, i) => (
            <p key={i} className="text-xs text-orange-700">
              ⚠ {w}
            </p>
          ))}
        </div>
      )}

      {/* Actions */}
      {(state === "done" || state === "error") && (
        <div className="flex justify-end gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={reset}
            className="text-xs"
          >
            Record Again
          </Button>
        </div>
      )}

      {state === "idle" && (
        <p className="text-xs text-gray-400 text-center">
          Example: "Paracetamol 650 mg tablet, SOS after meal for 3 days."
        </p>
      )}
    </div>
  );
}
