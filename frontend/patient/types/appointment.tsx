export interface Doctor {
    id: string;
    name: string;
    specialty: string;
    rating: number;
    reviews: number;
    experience: string;
    location: string;
    languages: string[];
    fee: number;
    image: string;
    verified: boolean;
    education?: string[];
    summary?: string;
    availability?: {
        day: string;
        slots: string[];
    }[];
}

export interface Appointment {
    id: string;
    doctorId: string;
    doctorName: string;
    doctorImage: string;
    date: string;
    time: string;
    status: 'upcoming' | 'completed' | 'cancelled';
    type: 'video' | 'in-person';
    reason?: string;
}

export interface appointmentProps {
    appointment_id?: string;
    status?: string;
    image?: string;
    consultation_type?: string;
    consultation_fee?: string;
    name?: string;
    speciality?: string;
    doctor_name?: string;
    doctor_id?: string;
    rating?: number;
    experience?: string;
    years_experience?: string;
    date?: string;
    time?: string;
    average_rating?: number;
    call_now?: boolean;
    join_url?: string;
}

export type AppointmentResponse = {

    appointment_id: string;
    slug: string;
    status: string;
    status_label: string;
    notes?: string;
    can_start_consultation: boolean;
    can_cancel: boolean;
    can_reschedule: boolean;
    appointment_date: string;
    appointment_date_formatted: string;
    appointment_time: string;
    appointment_time_formatted: string;
    consultation_type: "video" | "in-person";
    consultation_type_label: string;
    fee_amount: string;
    ratings_count: number;
    average_rating?: number;
    // Optional live-consultation fields
    call_now?: boolean;
    join_url?: string;
    video_consultation?: {
        join_url: string;
    };

    schedule: {
        date: string;
        date_formatted: string;
        time: string;
        time_formatted: string;
        booking_type: string;
        consultation_type: "video" | "in-person";
        consultation_type_label: string;
    };

    doctor?: {
        id: string;
        name: string;
        avatar: string;
        department: string;
        slug: string;
        average_rating?: number;
        years_experience?: string;
        user_id?: string;
    };
};

export interface PaginationInfo {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

export type AppointmentListResponse = {
    status: boolean;
    data: AppointmentResponse[];
    pagination: PaginationInfo;
};