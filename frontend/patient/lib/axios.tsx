import axios from "axios";
import { getAuthToken } from "./authToken";

const api = axios.create({
    baseURL: process.env.NEXT_PUBLIC_API_BASE_URL,
    timeout: 30000,
    headers: {
        Accept: "application/json",
    },
});

api.interceptors.request.use((config: any) => {
    const token = getAuthToken();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;