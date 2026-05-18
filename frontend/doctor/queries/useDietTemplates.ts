import { getDietTemplates, getPatientDietPlan } from "@/api/diet-template";
import { useQuery } from "@tanstack/react-query";


export const useDietTemplates = () => {
    return useQuery({
        queryKey: ["diet-templates"],
        queryFn: getDietTemplates,
    });
};


export const usePatientDietPlan = () => {
    return useQuery({
        queryKey: ["patient-diet-plan"],
        queryFn: getPatientDietPlan,
    });
};