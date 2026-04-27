import api from "@/lib/axios";
import { AppointmentListResponse } from "@/types/appointment";

export const fetchAppointments = async (
    filter: "upcoming" | "past",
    page: number = 1
): Promise<AppointmentListResponse> => {
    const { data } = await api.get(`/appointments/my?filter=${filter}&page=${page}`);
    return data;
};  