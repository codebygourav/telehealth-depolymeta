import axiosInstance from "@/lib/axios";
import type { GetAllPatientsResponse } from "@/types/patients";

export interface GetAllPatientsParams {
  page?: number;
  per_page?: number;
  search?: string;
}

export const getAllPatients = async ({
  page = 1,
  per_page = 10,
  search = "",
}: GetAllPatientsParams = {}): Promise<GetAllPatientsResponse> => {
  const response = await axiosInstance.get<GetAllPatientsResponse>(
    "/doctor/all-patients",
    {
      params: {
        page,
        per_page,
        ...(search ? { search } : {}),
      },
    }
  );

  return response.data;
};