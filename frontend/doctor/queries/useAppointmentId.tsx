import { fetchAppointmentById } from "@/api/appoitments";
import { useQuery } from "@tanstack/react-query";

export const useAppointmentById = (id: string) => {
    return useQuery({
        queryKey: ["appointment", id],
        queryFn: () => fetchAppointmentById(id),
        enabled: !!id,
        staleTime: 60 * 1000, // 1 minute
        gcTime: 5 * 60 * 1000, // 5 minutes
    });
};