import { fetchMyTransactions } from "@/api/transactions";
import { useQuery } from "@tanstack/react-query";

export const useTransactions = () => {
    return useQuery({
        queryKey: ["transactions"],
        queryFn: fetchMyTransactions,
        staleTime: 3 * 60 * 1000,
        retry: 0,
        refetchOnWindowFocus: false,
        refetchOnReconnect: false,
    });
};      
