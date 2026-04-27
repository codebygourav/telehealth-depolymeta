import { useMutation, useQueryClient } from "@tanstack/react-query";
import { rescheduleAppointment } from "@/api/rescheduleAppointments";
import type { RescheduleAppointmentPayload } from "@/types/reschedule";

export const useRescheduleAppointment = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: RescheduleAppointmentPayload) =>
      rescheduleAppointment(payload),

    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["doctor-available-slots"] });
      queryClient.invalidateQueries({ queryKey: ["appointments"] });
    },
  });
};