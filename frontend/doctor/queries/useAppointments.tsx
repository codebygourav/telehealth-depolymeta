import { fetchMyAppointments } from "@/api/appoitments";
import { getAuthToken } from "@/lib/authToken";
import { useQuery } from "@tanstack/react-query";

export function useMyAppointments(filter: string) {
    const token = getAuthToken();

    return useQuery({
        queryKey: ["my-appointments", filter],
        queryFn: () => fetchMyAppointments(filter),
        enabled: !!token,
        retry: 0,
        staleTime: 5 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
    });
}