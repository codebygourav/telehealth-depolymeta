
import { completeDietMeal, getDietTemplates, getPatientDietPlan,} from "@/api/diet-template";
import { useMutation, useQuery } from "@tanstack/react-query";


export const useDietTemplates = () => {
    return useQuery({
        queryKey: ["diet-templates"],
        queryFn: getDietTemplates,
    });
};


export const usePatientDietPlan = (patientId?: string) => {
    return useQuery({
        queryKey: ["patient-diet-plan", patientId],
        queryFn: () => getPatientDietPlan(patientId!),
        enabled: !!patientId,
    });
};




export const useCompleteDietMeal = () => {
    return useMutation({
        mutationFn: completeDietMeal,
    });
};