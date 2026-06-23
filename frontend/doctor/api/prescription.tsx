// ✅ Prescription Detail API
import api from "@/lib/axios";

export const fetchPrescriptionByAppointmentId = async (appointmentId: string) => {
    const { data } = await api.get(`/prescriptions/detail/${appointmentId}`);

    return data;
};

export const deletePrescriptionItem = async (prescriptionId: string) => {
    const { data } = await api.delete(`/prescriptions/${prescriptionId}`);

    return data;
};