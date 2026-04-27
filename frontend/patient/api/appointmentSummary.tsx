import axiosInstance from "@/lib/axios";
import type { AppointmentDetailResponse } from "@/types/appointment-summary";

interface GetAppointmentDetailParams {
  appointmentId: string;
}

export const getAppointmentDetail = async ({
  appointmentId,
}: GetAppointmentDetailParams): Promise<AppointmentDetailResponse> => {
  const response = await axiosInstance.get<AppointmentDetailResponse>(
    `/appointments/${appointmentId}`
  );

  return response.data;
};