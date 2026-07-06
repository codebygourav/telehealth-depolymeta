import { parsePrescriptionDraftText, type PrescriptionDraftParsePayload } from "@/api/prescription-drafts";
import { useMutation } from "@tanstack/react-query";

export const useParsePrescriptionDraft = (appointmentId: string) => {
  return useMutation({
    mutationFn: (payload: PrescriptionDraftParsePayload) =>
      parsePrescriptionDraftText(appointmentId, payload),
  });
};
