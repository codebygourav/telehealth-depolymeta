import api from "@/lib/axios";

export interface VoiceTranscriptionResult {
  transcript: string;
  confidence: number | null;
  duration_seconds: number;
  credits_used: number;
  log_id: string;
  draft_id: string | null;
  form: Record<string, unknown> | null;
  warnings: string[];
  missing_fields: string[];
}

export const transcribeVoice = async (
  appointmentId: string,
  audioBlob: Blob,
  language = "en",
): Promise<VoiceTranscriptionResult> => {
  const formData = new FormData();
  // Deepgram accepts webm natively
  const ext = audioBlob.type.includes("ogg") ? "ogg" : "webm";
  formData.append("audio", audioBlob, `recording.${ext}`);
  formData.append("language", language);

  const { data } = await api.post(
    `/doctor/${appointmentId}/prescription-drafts/voice`,
    formData,
    { headers: { "Content-Type": "multipart/form-data" } },
  );

  return data.data ?? data;
};
