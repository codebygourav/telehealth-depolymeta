import { fetchPrescriptionByAppointmentId } from "@/api/prescription";
import { useQuery } from "@tanstack/react-query";


export const usePrescriptionByAppointmentId = (appointmentId: string) => {
    return useQuery({
        queryKey: ["prescription", appointmentId],
        queryFn: () => fetchPrescriptionByAppointmentId(appointmentId),
        enabled: !!appointmentId, // id hona chahiye
    });
};