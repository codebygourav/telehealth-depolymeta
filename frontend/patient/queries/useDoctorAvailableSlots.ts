import { useQuery } from "@tanstack/react-query";
import { getDoctorAvailableSlots } from "@/api/slots";

export const doctorSlotKeys = {
  all: ["doctor-available-slots"] as const,
  detail: (doctorId: string) =>
    [...doctorSlotKeys.all, doctorId] as const,
};

export const useDoctorAvailableSlots = (
  doctorId: string,
  enabled: boolean
) => {
  return useQuery({
    queryKey: doctorSlotKeys.detail(doctorId),
    queryFn: () => getDoctorAvailableSlots(doctorId),
    enabled: !!doctorId && enabled,
  });
};