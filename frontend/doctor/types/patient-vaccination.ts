export interface DoctorPatientVaccination {
    id: string;
    dose_no?: number;
    set_name?: string | null;
    set_sort_order?: number | null;
    recommended_age_label?: string | null;
    assigned_date?: string | null;
    first_dose_date?: string | null;
    due_after_days?: number | null;
    due_after_months?: number | null;
    patient_age?: string | null;
    patient_age_on_schedule?: string | null;
    scheduled_date?: string | null;
    completed_date?: string | null;
    vaccination_template_id?: string | null;
    status?: string | null;
    status_label?: string | null;
    effective_status?: string | null;
    effective_status_label?: string | null;
    is_overdue?: boolean;
    doctor_notes?: string | null;
    documents?: {
        id: string;
        document?: string | null;
        document_url?: string | null;
        document_type?: string | null;
        certificate_number?: string | null;
    }[];
    vaccination?: {
        id: string;
        name: string;
        short_name?: string | null;
        manufacturer?: string | null;
        disease_for?: string | null;
    };
    expected_date?: string | null;
    due_date?: string | null;
    changed_date?: string | null;
    overdue_date?: string | null;
    missed_date?: string | null;
    grace_period_before_days?: number | null;
    grace_period_after_days?: number | null;
    skipped_reason?: string | null;
    on_hold_reason?: string | null;
    logs?: {
        id: string;
        action: string;
        old_value?: string | null;
        new_value?: string | null;
        reason?: string | null;
        performed_by?: {
            id: string;
            name: string;
        } | null;
        created_at: string;
    }[];
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
