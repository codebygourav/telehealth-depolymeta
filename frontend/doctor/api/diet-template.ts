import axiosInstance from "@/lib/axios";

import { GetDietTemplatesResponse } from "@/types/diet-template";


export const getDietTemplates = async (): Promise<GetDietTemplatesResponse> => {
    const response = await axiosInstance.get<GetDietTemplatesResponse>(
        "/doctor/diet/templates"
    );

    return response.data;
};

export const assignDietTemplate = async (payload: {
    patient_id: string;
    template_id: string;
    start_date: string;
    duration_days: number;
    special_instructions?: string;
    doctor_remark?: string;
}) => {
    const response = await axiosInstance.post(
        "/doctor/diet/assign",
        payload
    );

    return response.data;
};


export const getPatientDietPlan = async (patientId: string) => {
    const response = await axiosInstance.get(
        `/doctor/${patientId}/diet-plan`
    );

    return response.data;
};


// Assigned Diet Chart table follow button

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
        `/doctor/diet/meal/${mealId}/complete`,
        {
            date: occurrenceDate,
            notes: notes || null,
        }
    );

    return response.data;
};
