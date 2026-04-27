import axiosInstance from "@/lib/axios";
import type { BrowseDoctorsResponse } from "@/types/browse-doctors";

export const getBrowseDoctors = async (): Promise<BrowseDoctorsResponse> => {
  const response = await axiosInstance.get<BrowseDoctorsResponse>(
    "/patient/browse-doctors"
  );

  return response.data;
};