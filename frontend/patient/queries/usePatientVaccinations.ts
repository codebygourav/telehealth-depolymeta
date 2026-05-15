import { getPatientVaccinations } from "@/api/patient-vaccinations";
import { useQuery } from "@tanstack/react-query";


export const usePatientVaccinations = () => {
    return useQuery({
        queryKey: ["patient-vaccinations"],
        queryFn: getPatientVaccinations,
    });
};