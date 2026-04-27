import api from "@/lib/axios";

export const addPrescription = async (
    appointmentId: string,
    payload: any,
    token: string
) => {
    const response = await api.post(
        `/doctor/${appointmentId}/prescriptions`,
        payload,
        {
            headers: {
                Authorization: `Bearer ${token}` 
            }
        }
    );

    return response.data;
};
