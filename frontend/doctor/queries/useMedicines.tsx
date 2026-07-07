import { useQuery } from "@tanstack/react-query";
import { getMedicines } from "@/api/medicines";

interface UseMedicinesParams {
    page: number;
    per_page: number;
    search: string;
    include_doctor_added?: boolean;
}

export const useMedicines = ({
    page,
    per_page,
    search,
    include_doctor_added = false,
}: UseMedicinesParams) => {
    return useQuery({
        queryKey: ["medicines", page, per_page, search, include_doctor_added],
        queryFn: () =>
            getMedicines({
                page,
                per_page,
                search,
                include_doctor_added,
            }),
        placeholderData: (previousData) => previousData,
        staleTime: 60 * 1000, // 1 minute
        gcTime: 5 * 60 * 1000, // 5 minutes
    });
};
