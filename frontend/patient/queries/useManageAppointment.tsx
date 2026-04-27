import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { updateAppointmentInformation, UpdateAppointmentInformationParams, deleteMedicalReport, getPatientMedicalReports } from "@/api/manageAppointment";
import { appointmentDetailKeys } from "@/queries/useAppointmentSummary";

export const useUpdateAppointmentInformation = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (data: UpdateAppointmentInformationParams) => updateAppointmentInformation(data),
        onSuccess: (_, variables) => {
            queryClient.invalidateQueries({
                queryKey: appointmentDetailKeys.detail(variables.appointmentId),
            });
        },
    });
};

export const useDeleteMedicalReport = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (reportId: string) => deleteMedicalReport(reportId),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: appointmentDetailKeys.all,
            });
        },
    });
};

export const usePatientMedicalReports = (patientId: string) => {
    return useQuery({
        queryKey: ['patient-medical-reports', patientId],
        queryFn: () => getPatientMedicalReports(patientId),
        enabled: !!patientId,
    });
};
