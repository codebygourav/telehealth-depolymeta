import { useQuery } from "@tanstack/react-query";
import { getMedicines } from "@/api/medicines";

interface UseMedicinesParams {
  page: number;
  per_page: number;
  search: string;
}

export const useMedicines = ({
  page,
  per_page,
  search,
}: UseMedicinesParams) => {
  return useQuery({
    queryKey: ["medicines", page, per_page, search],
    queryFn: () =>
      getMedicines({
        page,
        per_page,
        search,
      }),
    placeholderData: (previousData) => previousData,
    staleTime: 60 * 1000, // 1 minute
    gcTime: 5 * 60 * 1000, // 5 minutes
  });
};