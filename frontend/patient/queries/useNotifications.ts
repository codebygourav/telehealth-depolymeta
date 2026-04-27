// useNotifications.ts
import { useQuery } from "@tanstack/react-query";
import { fetchNotifications, fetchUnreadCount } from "@/api/notifications";

export const useNotifications = (page: number = 1) => {
    return useQuery({
        queryKey: ["notifications", page],
        queryFn: () => fetchNotifications(page), 
    });
};

export const useUnreadCount = () => {
    return useQuery({
        queryKey: ["unread-count"],
        queryFn: fetchUnreadCount,
    });
};