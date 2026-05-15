import { getVaccinationTemplates } from "@/api/vaccination-temp";
import { useQuery } from "@tanstack/react-query";


export const useVaccinationTemplates = () => {
    return useQuery({
        queryKey: ["vaccination-templates"],
        queryFn: getVaccinationTemplates,
    });
};