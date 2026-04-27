import { getAuthToken } from "@/lib/authToken";
import api from "@/lib/axios";

export const fetchAppointmentById = async (id: string, token?: string) => {
    const url = `/appointments/${id}`;
    const authToken = token || getAuthToken();
    try {
        const response = await api.get(url, {
            headers: authToken ? { Authorization: `Bearer ${authToken}` } : undefined
        });
        // Return the data
        return response.data?.data || response.data;
    } catch (error: any) {
        throw error;
    }
};