import { assignMedicineTemplate } from "@/api/medicine-templates";
import type { AssignMedicineTemplatePayload } from "@/types/medicine-template";
import { useMutation, useQueryClient } from "@tanstack/react-query";

export const useAssignMedicineTemplate = (appointmentId: string) => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: AssignMedicineTemplatePayload) =>
      assignMedicineTemplate(appointmentId, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({
        queryKey: ["prescription", appointmentId],
      });
      queryClient.invalidateQueries({ queryKey: ["patient-detail"] });
      queryClient.invalidateQueries({ queryKey: ["appointment"] });
    },
  });
};
