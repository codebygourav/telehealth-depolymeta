import { useMutation, useQueryClient } from "@tanstack/react-query";
import { uploadMedicalReport, UploadMedicalReportParams } from "@/api/uploadMedicalReport";
import { toast } from "sonner";

export function useUploadMedicalReport() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (params: UploadMedicalReportParams) => uploadMedicalReport(params),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["medical-reports"] });
        },
        onError: (error: any) => {
            const message = error?.response?.data?.message || "Failed to upload report";
            toast.error(message);
        },
    });
}
