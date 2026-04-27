import axiosInstance from "@/lib/axios";
import type { DoctorDetailResponse } from "@/types/doctor-details";

interface GetDoctorDetailParams {
  userId: string;
}

export const getDoctorDetail = async ({
  userId,
}: GetDoctorDetailParams): Promise<DoctorDetailResponse> => {
  const response = await axiosInstance.get<DoctorDetailResponse>(
    `/patient/browse-doctor/${userId}`
  );

  return response.data;
};