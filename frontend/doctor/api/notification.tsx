import { NotificationsResponse } from "@/types/notification";
import axios from "axios";
import { getAuthToken } from "@/lib/authToken";

const baseURL =
  process.env.NEXT_PUBLIC_API_URL || process.env.NEXT_PUBLIC_API_BASE_URL || "";

const notificationsApi = axios.create({
  baseURL,
});

notificationsApi.interceptors.request.use((config) => {
  const token = getAuthToken();

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

notificationsApi.interceptors.response.use(
  (response) => response,
  (error) => {
    if (typeof window !== "undefined" && error?.response?.status === 401) {
      window.dispatchEvent(new CustomEvent("auth:unauthorized"));
    }

    return Promise.reject(error);
  },
);

export const getNotifications = async (): Promise<NotificationsResponse> => {
  const response = await notificationsApi.get("/notifications");
  return response.data;
};

export const getSingleNotification = async (notificationId: string) => {
  const response = await notificationsApi.get(
    `/notifications/${notificationId}`,
  );
  return response.data;
};

export const getUnreadCount = async (): Promise<number> => {
  const response = await notificationsApi.get("/notifications/unread-count");
  return Number(
    response.data?.data?.unread_count ?? response.data?.unread_count ?? 0,
  );
};

export const storePushSubscription = async (subscription: any) => {
  const { data } = await notificationsApi.post("/notifications/push-subscription", subscription);
  return data;
};

export const deletePushSubscription = async (endpoint: string) => {
  const { data } = await notificationsApi.post("/notifications/push-subscription/delete", { endpoint });
  return data;
};
