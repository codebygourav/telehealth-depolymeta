import axiosInstance from "@/lib/axios";
import { GetPatientVaccinationsResponse } from "@/types/patient-vaccination";


export const getPatientVaccinations =
    async (
        page = 1,
        perPage = 10,
        filter: 'all' | 'completed' | 'upcoming' = 'all'
    ): Promise<GetPatientVaccinationsResponse> => {
        const response =
            await axiosInstance.get<GetPatientVaccinationsResponse>(
                "/patient/vaccinations",
                {
                    params: {
                        page,
                        per_page: perPage,
                        filter,
                    },
                }
            );

        return response.data;
    };