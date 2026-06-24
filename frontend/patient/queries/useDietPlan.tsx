import { useQuery, useMutation } from "@tanstack/react-query";
import { getDietPlan, completeDietMeal } from "@/api/dietPlan";


export function useDietPlan() {
    return useQuery({
        queryKey: ["diet-plan"],
        queryFn: () => getDietPlan(),
    });
}

export function useCompleteDietMeal() {
    return useMutation({
        mutationFn: completeDietMeal,
    });
}