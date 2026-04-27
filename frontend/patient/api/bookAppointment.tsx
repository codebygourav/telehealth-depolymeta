import axiosInstance from "@/lib/axios";
import type {
  BookAppointmentPayload,
  BookAppointmentResponse,
} from "@/types/book-appointment";

export const bookAppointment = async (
  payload: BookAppointmentPayload
): Promise<BookAppointmentResponse> => {
  const formData = new FormData();

  formData.append("doctor_id", payload.doctor_id);
  formData.append("availability_id", payload.availability_id);
  formData.append("appointment_date", payload.appointment_date);
  formData.append("appointment_time", payload.appointment_time);
  formData.append("consultation_type", payload.consultation_type);
  formData.append("opd_type", payload.opd_type);

  if (payload.notes) {
    formData.append("notes", payload.notes);
  }

  const response = await axiosInstance.post<BookAppointmentResponse>(
    "/book-appointment",
    formData,
    {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    }
  );

  return response.data;
};