import { getPatientVaccinations } from "@/api/patient-vaccinations";
import { useQuery } from "@tanstack/react-query";

export const usePatientVaccinations = (
    patientId?: string,
    page = 1,
    perPage = 10,
    filter: 'all' | 'completed' | 'upcoming' = 'all',
    search = ''
) => {
    return useQuery({
        queryKey: ["patient-vaccinations", patientId, page, perPage, filter, search],
        queryFn: () => getPatientVaccinations(patientId!, page, perPage, filter, search),
        enabled: !!patientId,
    });
};
