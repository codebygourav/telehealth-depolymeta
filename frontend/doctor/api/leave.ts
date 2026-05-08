import axiosInstance from "@/lib/axios";
import type { GetLeavesResponse } from "@/types/leave";

export interface GetLeavesParams {
    page?: number;
    per_page?: number;
    search?: string;
}

export const getLeaves = async ({
    page = 1,
    per_page = 5,
    search = "",
}: GetLeavesParams = {}): Promise<GetLeavesResponse> => {
    const response = await axiosInstance.get<GetLeavesResponse>("/leave/my", {
        params: {
            page,
            per_page,
            ...(search ? { search } : {}),
        },
    });

    return response.data;
};