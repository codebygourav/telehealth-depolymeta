import axiosInstance from "@/lib/axios";
import { DietPlanResponse } from "@/types/meal-chart";


export const getDietPlan = async (): Promise<DietPlanResponse> => {
    const response = await axiosInstance.get<DietPlanResponse>(
        `/patient/diet-plan`
    );
    return response.data;
};