import axiosInstance from "@/lib/axios";
import type { GetPatientGroupedReportsResponse } from "@/types/patient-reports";

interface GetPatientReportsParams {
  page?: number;
  per_page?: number;
  filter?: string;
}

export const getPatientReports = async ({
  page = 1,
  per_page = 10,
  filter,
}: GetPatientReportsParams = {}): Promise<GetPatientGroupedReportsResponse> => {
  const response = await axiosInstance.get<GetPatientGroupedReportsResponse>(
    "/doctor/all-reports",
    {
      params: {
        page,
        per_page,
        ...(filter ? { filter } : {}),
      },
    }
  );

  return response.data;
};