export interface PatientVaccination {
    id: string;
    status?: string;
    due_date?: string;

    vaccination?: {
        id: string;
        name: string;
        short_name?: string;
        manufacturer?: string;
    };
}

export interface VaccinationInformation {
    id: string;
    short_name?: string;
    created_at?: string;
    disease_for?: string;
    description?: string;
    side_effects?: string;
    contraindications?: string;
    prevention?: string;
    precautions?: string;
    dosage_information?: string;
    is_multi_dose?: boolean;
    total_doses?: number;
    faqs?: VaccinationFaq[];
}

export interface ScheduleVaccination {
    id: string;
    vaccine_name: string;
    short_description: string;
    recommended_age: string;
    due_date: string;
    scheduled_date?: string;
    assigned_date?: string;
    due_after_days?: number | null;
    due_after_months?: number | null;
    patient_age?: string | null;
    patient_age_on_schedule?: string | null;
    dose_no: number;
    status: string;
    status_label: string;
    effective_status?: string;
    effective_status_label?: string;
    is_overdue?: boolean;
    manufacturer?: string | null;
    doctor_notes?: string | null;
    expected_date?: string | null;
    completed_date?: string | null;
    changed_date?: string | null;
    overdue_date?: string | null;
    missed_date?: string | null;
    grace_period_before_days?: number | null;
    grace_period_after_days?: number | null;
    skipped_reason?: string | null;
    on_hold_reason?: string | null;
    information?: VaccinationInformation;
    documents?: {
        id: string;
        document_url: string;
        document_type: string;
        certificate_number?: string;
    }[];
}

export interface VaccinationScheduleSet {
    set_id: number;
    set_sort_order?: number;
    set_name: string;
    description: string;
    status: string;
    expanded: boolean;
    vaccinations: ScheduleVaccination[];
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

export interface ClinicalInsight {
    title: string;
    message: string;
}



export interface PatientVaccinationData {
    vaccination_summary: VaccinationSummary;
    vaccination_schedule: VaccinationScheduleSet[];
    faqs: VaccinationFaq[];
    clinical_insight: ClinicalInsight;
    pagination?: PaginationMeta;
}




export interface VaccinationSummary {
    completed_count: number;
    completed_percentage: number;
    due_count?: number;
    overdue_count?: number;
    pending_count?: number;
    scheduled_count?: number;
    next_due_date: string;
    total_count: number;
}


export interface GetPatientVaccinationsResponse {
    success: boolean;
    data: PatientVaccinationData;
}

export interface VaccinationFaq {
    id: string;
    question: string;
    answer: string;
    sort_order: number;
}
