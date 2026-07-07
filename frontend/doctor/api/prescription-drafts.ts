import api from "@/lib/axios";

export interface PrescriptionDraftParsePayload {
  input_text: string;
  source_type?: "text" | "speech";
}

export const parsePrescriptionDraftText = async (
  appointmentId: string,
  payload: PrescriptionDraftParsePayload,
) => {
  const { data } = await api.post(
    `/doctor/${appointmentId}/prescription-drafts/text`,
    payload,
  );

  return data;
};
