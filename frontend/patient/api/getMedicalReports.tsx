import api from "@/lib/axios";
import { MedicalReportsResponse } from "@/types/medical-reports";

export const fetchMedicalReports = async (patientId: string, page: number
): Promise<MedicalReportsResponse & { meta: any }> => {
    const { data } = await api.get(
        `/patient/${patientId}/medical-reports`,
        { params: { page } }
    );
    return data;
};