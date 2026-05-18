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
}) => {
    const response = await axiosInstance.post(
        "/doctor/diet/assign",
        payload
    );

    return response.data;
};


export const getPatientDietPlan = async () => {
    const response = await axiosInstance.get("/patient/diet-plan");

    return response.data;

};