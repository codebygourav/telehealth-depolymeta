import { useQuery } from "@tanstack/react-query";
import { getDietPlan } from "@/api/dietPlan";


export function useDietPlan() {
    return useQuery({
        queryKey: ["diet-plan"],
        queryFn: () => getDietPlan(),
    });
}