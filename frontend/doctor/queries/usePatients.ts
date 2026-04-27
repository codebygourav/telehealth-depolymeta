import { useQuery } from "@tanstack/react-query";
import { getAllPatients } from "@/api/patients";

interface UsePatientsParams {
  page: number;
  per_page: number;
  search: string;
}

export const usePatients = ({
  page,
  per_page,
  search,
}: UsePatientsParams) => {
  return useQuery({
    queryKey: ["doctor-all-patients", page, per_page, search],
    queryFn: () =>
      getAllPatients({
        page,
        per_page,
        search,
      }),
    placeholderData: (previousData) => previousData,
    staleTime: 60 * 1000, // 1 minute
    gcTime: 5 * 60 * 1000, // 5 minutes
  });
};