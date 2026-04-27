// types/review.ts

export interface Review {
    id: string;
    patient_name: string;
    patient_image: string;
    title: string;
    content: string;
    rating: number;
    doctor_name: string;
    doctor_avatar: string;
    doctor_departments: string;
    created_at: string;
}

export interface ReviewResponse {
    success: boolean;
    message: string;
    data: Review[];
    pagination: {
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
}