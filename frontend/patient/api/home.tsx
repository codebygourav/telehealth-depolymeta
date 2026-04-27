import axiosInstance from "@/lib/axios";
import type { GetPatientHomeResponse } from "@/types/home";

export const getPatientHome = async (): Promise<GetPatientHomeResponse> => {
  const response = await axiosInstance.get<GetPatientHomeResponse>("/patient/home");
  return response.data;
};