import axiosInstance from "@/lib/axios";
import type {
  RescheduleAppointmentPayload,
  RescheduleAppointmentData,
} from "@/types/reschedule";

export const rescheduleAppointment = async (
  payload: RescheduleAppointmentPayload
): Promise<RescheduleAppointmentData> => {
  const response = await axiosInstance.post<RescheduleAppointmentData>(
    "/appointments/reschedule",
    payload
  );

  return response.data;
};