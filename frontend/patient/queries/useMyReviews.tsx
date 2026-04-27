import { useQuery, keepPreviousData } from "@tanstack/react-query";
import { fetchMyReviews } from "@/api/review";
import { ReviewResponse } from "@/types/reviews";


export const useMyReviews = (page: number) => {
    return useQuery<ReviewResponse>({
        queryKey: ["my-reviews", page],
        queryFn: () => fetchMyReviews(page, 1),
        placeholderData: keepPreviousData,
    });
};