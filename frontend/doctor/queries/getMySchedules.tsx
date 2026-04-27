import { fetchSchedule } from "@/api/schedule";
import { useQuery } from "@tanstack/react-query";

export function useMySchedules() {

    return useQuery({
        queryKey: ["my-appointments"],
        queryFn: () => fetchSchedule(),
        retry: 0,
        staleTime: 5 * 60 * 1000,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
    });
}