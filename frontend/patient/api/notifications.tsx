// lib/axios.ts or wherever your API functions are
import api from "@/lib/axios";

//! get all notifications data 
export const fetchNotifications = async (page = 1) => {
    const { data } = await api.get(`/notifications?page=${page}`);
    return data;
};

//! unread count function
export const fetchUnreadCount = async () => {
    const { data } = await api.get("/notifications/unread-count");
    return data?.data?.unread_count; // 👈 direct number return
};


//! mark all read function
export const markAllAsRead = async () => {
    const { data } = await api.post("/notifications/read-all");
    return data;
};

//! mark single notification as read
export const markNotificationAsRead = async (notificationId: string) => {
    const { data } = await api.post(`/notifications/${notificationId}/read`);
    return data;
};

//! store push subscription
export const storePushSubscription = async (subscription: any) => {
    const { data } = await api.post("/notifications/push-subscription", subscription);
    return data;
};

//! delete push subscription
export const deletePushSubscription = async (endpoint: string) => {
    const { data } = await api.post("/notifications/push-subscription/delete", { endpoint });
    return data;
};