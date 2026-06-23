import { deletePrescriptionItem } from "@/api/prescription";
import { useMutation, useQueryClient } from "@tanstack/react-query";

export const useDeletePrescriptionItem = (appointmentId: string) => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (prescriptionId: string) => deletePrescriptionItem(prescriptionId),

        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["patient-detail"] });
            queryClient.invalidateQueries({ queryKey: ["appointment"] });
            queryClient.invalidateQueries({ queryKey: ["prescription", appointmentId] });
        },
    });
};
