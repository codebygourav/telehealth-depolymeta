import { useQuery } from "@tanstack/react-query";
import { getTransactions } from "@/api/transactions";

interface UseTransactionsParams {
  page: number;
  per_page: number;
  search: string;
}

export const useTransactions = ({
  page,
  per_page,
  search,
}: UseTransactionsParams) => {
  return useQuery({
    queryKey: ["transactions", page, per_page, search],
    queryFn: () =>
      getTransactions({
        page,
        per_page,
        search,
      }),
    placeholderData: (previousData) => previousData,
    staleTime: 60 * 1000, // 1 minute
    gcTime: 5 * 60 * 1000, // 5 minutes
  });
};