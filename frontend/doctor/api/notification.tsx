import { NotificationsResponse } from "@/types/notification";
import axios from "axios";

const baseURL = process.env.NEXT_PUBLIC_API_URL || process.env.NEXT_PUBLIC_API_BASE_URL;

if (!baseURL) {
  throw new Error("NEXT_PUBLIC_API_URL or NEXT_PUBLIC_API_BASE_URL is not defined");
}

const notificationsApi = axios.create({
  baseURL,
});

notificationsApi.interceptors.request.use((config) => {
  if (typeof window !== "undefined") {
    const token =
      localStorage.getItem("@token") ||
      localStorage.getItem("doctorToken") ||
      localStorage.getItem("token") ||
      localStorage.getItem("access_token");

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }

  return config;
});

export const getNotifications = async (): Promise<NotificationsResponse> => {
  const response = await notificationsApi.get("/notifications");
  return response.data;
};

export const getSingleNotification = async (notificationId: string) => {
  const response = await notificationsApi.get(`/notifications/${notificationId}`);
  return response.data;
};

export const getUnreadCount = async (): Promise<any> => {
  const response = await notificationsApi.get("/notifications/unread-count");
  return response.data;
};
