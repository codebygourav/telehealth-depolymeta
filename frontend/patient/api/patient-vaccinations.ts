import axiosInstance from "@/lib/axios";
import { GetPatientVaccinationsResponse } from "@/types/patient-vaccination";


export const getPatientVaccinations =
    async (): Promise<GetPatientVaccinationsResponse> => {
        const response =
            await axiosInstance.get<GetPatientVaccinationsResponse>(
                "/patient/vaccinations"
            );

        return response.data;
    };