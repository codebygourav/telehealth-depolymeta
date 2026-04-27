import { getAppProfileScreens } from "@/api/appProfileScreens";
import { useQuery } from "@tanstack/react-query";

export const useAppProfileScreens = (token?: string) => {
    return useQuery({
        queryKey: ["app-profile-screens", token],
        queryFn: () => getAppProfileScreens(token),
        staleTime: 1000 * 60 * 60, // 1 hour
        gcTime: 1000 * 60 * 60 * 24, // 24 hours
        enabled: !!token, // Only run query if token exists
    });
};