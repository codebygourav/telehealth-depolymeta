import api from "@/lib/axios";
import { useMutation } from "@tanstack/react-query";

export const useCancelAppointment = () => {
    return useMutation({
        mutationFn: async (appointmentId: string) => {
            const formData = new FormData();
            formData.append('appointment_id', appointmentId);
            const { data } = await api.post("/appointments/cancel", formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            return data;
        },
    });
};