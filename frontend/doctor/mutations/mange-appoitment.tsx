import api from "@/lib/axios";

export const cancelAppointment = async (id: string) => {
    const { data } = await api.post("/appointments/cancel", {
        appointment_id: id,
    });

    return data;
};