import { getPatientVaccinations } from "@/api/patient-vaccinations";
import { useQuery } from "@tanstack/react-query";


export const usePatientVaccinations = (
    page = 1,
    perPage = 10,
    filter: 'all' | 'completed' | 'upcoming' = 'all'
) => {
    return useQuery({
        queryKey: ["patient-vaccinations", page, perPage, filter],
        queryFn: () => getPatientVaccinations(page, perPage, filter),
    });
};