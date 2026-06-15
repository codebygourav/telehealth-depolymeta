export interface DoctorPatientVaccination {
    id: string;
    dose_no?: number;
    set_name?: string | null;
    set_sort_order?: number | null;
    recommended_age_label?: string | null;
    scheduled_date?: string | null;
    status?: string | null;
    status_label?: string | null;
    vaccination?: {
        id: string;
        name: string;
        short_name?: string | null;
        manufacturer?: string | null;
        disease_for?: string | null;
    };
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

export interface GetDoctorPatientVaccinationsResponse {
    success: boolean;
    data: DoctorPatientVaccination[];
    pagination?: PaginationMeta;
    path?: string;
    timestamp?: string;
}
