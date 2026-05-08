import { useQuery } from "@tanstack/react-query";
import { getLeaves } from "@/api/leave";

interface UseLeavesParams {
    page: number;
    per_page: number;
    search: string;
}

export const useLeave = ({
    page,
    per_page,
    search,
}: UseLeavesParams) => {
    return useQuery({
        queryKey: ["leave", page, per_page, search],
        queryFn: () =>
            getLeaves({
                page,
                per_page,
                search,
            }),
        placeholderData: (previousData) => previousData,
        staleTime: 60 * 1000, // 1 minute
        gcTime: 5 * 60 * 1000, // 5 minutes
    });
};