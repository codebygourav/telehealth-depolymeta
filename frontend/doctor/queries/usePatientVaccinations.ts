import { getPatientVaccinations } from "@/api/patient-vaccinations";
import { useQuery } from "@tanstack/react-query";

export const usePatientVaccinations = (patientId?: string) => {
    return useQuery({
        queryKey: ["patient-vaccinations", patientId],
        queryFn: () => getPatientVaccinations(patientId!),
        enabled: !!patientId,
    });
};
