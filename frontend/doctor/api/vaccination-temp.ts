import axiosInstance from "@/lib/axios";
import { GetVaccinationTemplatesResponse } from "@/types/vaccination-template";


export const getVaccinationTemplates =
    async (): Promise<GetVaccinationTemplatesResponse> => {

        const response =
            await axiosInstance.get<GetVaccinationTemplatesResponse>(
                "/doctor/vaccination-templates"
            );

        return response.data;
    };