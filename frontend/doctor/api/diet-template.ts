import axiosInstance from "@/lib/axios";
import {
  AssignDietTemplatePayload,
  AssignDietTemplateResponse,
  DietTemplateListResponse,
} from "@/types/diet-template";

export const getDietTemplates = async (): Promise<DietTemplateListResponse> => {
  const response = await axiosInstance.get<DietTemplateListResponse>(
    "/doctor/diet/templates",
    {
      params: {
        active_only: true,
        per_page: 100,
      },
    },
  );

  return response.data;
};

export const assignDietTemplate = async (
  payload: AssignDietTemplatePayload,
): Promise<AssignDietTemplateResponse> => {
  const response = await axiosInstance.post<AssignDietTemplateResponse>(
    "/doctor/diet/assign",
    payload,
  );

  return response.data;
};
