import axiosInstance from "@/lib/axios";
import type { GetDoctorPatientVaccinationsResponse } from "@/types/patient-vaccination";

export const getPatientVaccinations = async (
    patientId: string,
    page = 1,
    perPage = 10,
    filter: 'all' | 'completed' | 'upcoming' = 'all',
    search = ''
): Promise<GetDoctorPatientVaccinationsResponse> => {
    const response = await axiosInstance.get<GetDoctorPatientVaccinationsResponse>(
        `/doctor/${patientId}/vaccinations`,
        {
            params: {
                page,
                per_page: perPage,
                filter,
                search: search.trim() || undefined,
            },
        }
    );

    return response.data;
};

export const assignVaccinationTemplate = async (
    patientId: string,
    templateId: string,
    firstDoseDate?: string
): Promise<unknown> => {
    const response = await axiosInstance.post(`/doctor/${patientId}/assign-template`, {
        template_id: templateId,
        ...(firstDoseDate ? { first_dose_date: firstDoseDate } : {}),
    });

    return response.data;
};

export const completePatientVaccination = async (
    vaccinationId: string,
    body: FormData | Record<string, unknown> = {}
): Promise<unknown> => {
    const response = await axiosInstance.post(
        `/doctor/patient-vaccinations/${vaccinationId}/complete`,
        body,
        body instanceof FormData
            ? { headers: { "Content-Type": "multipart/form-data" } }
            : undefined
    );
    return response.data;
};

export const updatePatientVaccination = async (
    vaccinationId: string,
    body: Record<string, unknown>
): Promise<unknown> => {
    const response = await axiosInstance.post(
        `/doctor/patient-vaccinations/${vaccinationId}`,
        body
    );
    return response.data;
};
