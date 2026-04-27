export interface NotificationItem {
  id: string;
  group: "appointment" | "review" | "availability" | "document" | string;
  title: string;
  desc: string;
  created_at: string;
  is_read: boolean;
}

export interface NotificationsResponse {
  unread_count: number;
  data: NotificationItem[];
  meta: {
    total_unread: number;
    total?: number;
  };
}
