import { fetchMedicalReports } from "@/api/getMedicalReports";
import { useQuery } from "@tanstack/react-query";

export function useMedicalReports(patientId?: string, page: number = 1) {
    return useQuery({
        queryKey: ["medical-reports", patientId, page],
        queryFn: () => fetchMedicalReports(patientId!, page),
        enabled: !!patientId,
    });
}