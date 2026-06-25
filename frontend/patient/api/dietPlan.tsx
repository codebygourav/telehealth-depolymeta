import axiosInstance from "@/lib/axios";
import { DietPlanResponse } from "@/types/meal-chart";


export const getDietPlan = async (): Promise<DietPlanResponse> => {
    const response = await axiosInstance.get<DietPlanResponse>(
        `/patient/diet-plan`
    );
    return response.data;
};

export const completeDietMeal = async ({
    mealId,
    occurrenceDate,
    notes,
}: {
    mealId: string;
    occurrenceDate: string;
    notes?: string | null;
}) => {
    const response = await axiosInstance.post(
        `/patient/diet/meal/${mealId}/complete`,
        {
            date: occurrenceDate,
            notes: notes || null,
        }
    );

    return response.data;
};