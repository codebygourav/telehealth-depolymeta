import api from "@/lib/axios";
import { DoctorReview } from "@/types/home";

export interface Pagination {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

export interface ReviewListResponse {
    success: boolean;
    message: string;
    pagination: Pagination;
    data: DoctorReview[];
    path: string;
    timestamp: string;
}

export const fetchFeedbacks = async (page: number = 1): Promise<ReviewListResponse> => {
    const { data } = await api.get(`/reviews/my?page=${page}`);
    return data;
};
