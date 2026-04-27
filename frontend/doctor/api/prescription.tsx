// ✅ Prescription Detail API
import api from "@/lib/axios";

export const fetchPrescriptionByAppointmentId = async (appointmentId: string) => {
    const { data } = await api.get(`/prescriptions/detail/${appointmentId}`);

    return data;
};