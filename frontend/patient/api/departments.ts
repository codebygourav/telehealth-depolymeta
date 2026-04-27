import axiosInstance from "@/lib/axios";
import type { DepartmentsAndSymptomsResponse } from "@/types/departments";

export const getDepartmentsAndSymptoms =
  async (): Promise<DepartmentsAndSymptomsResponse> => {
    const response = await axiosInstance.get<DepartmentsAndSymptomsResponse>(
      "/patient/departments-and-symptoms-list"
    );

    return response.data;
  };