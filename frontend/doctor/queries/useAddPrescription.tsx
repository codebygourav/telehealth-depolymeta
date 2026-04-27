import { addPrescription } from "@/api/addPrescription";
import { useMutation, useQueryClient } from "@tanstack/react-query";

export const useAddPrescription = (appointmentId: string, token: string) => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (payload: any) =>
            addPrescription(appointmentId, payload, token),

        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["patient-detail"] });
            queryClient.invalidateQueries({ queryKey: ["appointment"] });
            // Invalidate prescription query to refresh immediately
            queryClient.invalidateQueries({ queryKey: ["prescription", appointmentId] });
        },
    });
};
