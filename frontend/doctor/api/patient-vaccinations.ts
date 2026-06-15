import axiosInstance from "@/lib/axios";
import type { GetDoctorPatientVaccinationsResponse } from "@/types/patient-vaccination";

export const getPatientVaccinations = async (
    patientId: string
): Promise<GetDoctorPatientVaccinationsResponse> => {
    const response = await axiosInstance.get<GetDoctorPatientVaccinationsResponse>(
        `/doctor/${patientId}/vaccinations`
    );

    return response.data;
};

export const assignVaccinationTemplate = async (
    patientId: string,
    templateId: string
): Promise<unknown> => {
    const response = await axiosInstance.post(`/doctor/${patientId}/assign-template`, {
        template_id: templateId,
    });

    return response.data;
};

export const completePatientVaccination = async (
    vaccinationId: string,
    body: Record<string, unknown> = {}
): Promise<unknown> => {
    const response = await axiosInstance.post(`/doctor/patient-vaccinations/${vaccinationId}/complete`, body);
    return response.data;
};
