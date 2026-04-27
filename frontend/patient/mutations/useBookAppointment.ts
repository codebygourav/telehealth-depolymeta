import { useMutation } from "@tanstack/react-query";
import { AxiosError } from "axios";
import { bookAppointment } from "@/api/bookAppointment";
import type {
  BookAppointmentPayload,
  BookAppointmentResponse,
} from "@/types/book-appointment";

interface ApiErrorResponse {
  message?: string;
  errors?: Record<string, string[]>;
}

export const useBookAppointment = () => {
  return useMutation<
    BookAppointmentResponse,
    AxiosError<ApiErrorResponse>,
    BookAppointmentPayload
  >({
    mutationFn: bookAppointment,
  });
};