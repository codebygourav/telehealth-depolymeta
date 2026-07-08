"use client";

import {
  transcribeVoice,
  type VoiceTranscriptionResult,
} from "@/api/voice-transcription";
import { useMutation } from "@tanstack/react-query";

export function useVoiceTranscription(appointmentId: string) {
  return useMutation<
    VoiceTranscriptionResult,
    Error,
    { audioBlob: Blob; language?: string }
  >({
    mutationFn: ({ audioBlob, language }) =>
      transcribeVoice(appointmentId, audioBlob, language ?? "en"),
  });
}
