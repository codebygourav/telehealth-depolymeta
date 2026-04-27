import { useQuery } from "@tanstack/react-query";
import { getPatientReports } from "@/api/patient-reports";

interface UsePatientReportsParams {
    page?: number;
    per_page?: number;
    filter?: string;
}

export const usePatientReports = ({
    page = 1,
    per_page = 10,
    filter,
}: UsePatientReportsParams = {}) => {
    return useQuery({
        queryKey: ["patient-reports", page, per_page, filter],
        queryFn: () => getPatientReports({ page, per_page, filter }),
        staleTime: 5 * 60 * 1000,
        retry: 1,
    });
};