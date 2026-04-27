import axiosInstance from "@/lib/axios";
import type { GetDoctorHomeResponse } from "@/types/home";

export const getDoctorHome = async (): Promise<GetDoctorHomeResponse> => {
  const response = await axiosInstance.get<GetDoctorHomeResponse>("/doctor/home");
  return response.data;
};