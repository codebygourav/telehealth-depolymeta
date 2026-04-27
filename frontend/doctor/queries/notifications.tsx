import { getNotifications, getSingleNotification, getUnreadCount } from "@/api/notification";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

export enum NotificationQueryKeys {
  NOTIFICATIONS = "notifications",
  UNREAD_COUNT = "notifications_unread_count",
}

export function useNotifications() {
  return useQuery({
    queryKey: [NotificationQueryKeys.NOTIFICATIONS],
    queryFn: getNotifications,
    staleTime: 30 * 1000, // 30 seconds
    gcTime: 5 * 60 * 1000, // 5 minutes
  });
}

export function useUnreadCount() {
  return useQuery({
    queryKey: [NotificationQueryKeys.UNREAD_COUNT],
    queryFn: getUnreadCount,
    refetchInterval: 60000, // Optional: Poll every 1 minute
  });
}

export function useReadNotification() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => getSingleNotification(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [NotificationQueryKeys.NOTIFICATIONS] });
      queryClient.invalidateQueries({ queryKey: [NotificationQueryKeys.UNREAD_COUNT] });
    },
  });
}
