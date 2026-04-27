import { useQuery } from "@tanstack/react-query";
import { fetchFeedbacks } from "@/api/feedbacks";

export function useFeedbacks(page: number = 1) {
    return useQuery({
        queryKey: ["feedbacks", page],
        queryFn: () => fetchFeedbacks(page),
        staleTime: 60 * 1000, // 1 minute
        gcTime: 5 * 60 * 1000, // 5 minutes
    });
}