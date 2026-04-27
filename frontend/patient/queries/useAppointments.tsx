import { fetchAppointments } from "@/api/appointments";
import { useQuery } from "@tanstack/react-query";

export const useAppointments = (
    filter: "upcoming" | "past",
    page: number = 1
) => {
    return useQuery({
        queryKey: ["appointments", filter, page],
        queryFn: () => fetchAppointments(filter, page),
        staleTime: 0,
        retry: 0,
        refetchOnWindowFocus: true,
        refetchOnReconnect: true,
    });
};