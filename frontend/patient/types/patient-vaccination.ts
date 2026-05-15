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
    dose_no: number;
    status: string;
    status_label: string;
    manufacturer?: string | null;
    doctor_notes?: string | null;
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
    set_name: string;
    description: string;
    status: string;
    expanded: boolean;
    vaccinations: ScheduleVaccination[];
}

export interface ClinicalInsight {
    title: string;
    message: string;
}



export interface PatientVaccinationData {
    profile: PatientProfile;
    vaccination_summary: VaccinationSummary;
    vaccination_schedule: VaccinationScheduleSet[];
    faqs: VaccinationFaq[];
    clinical_insight: ClinicalInsight;
}




export interface PatientProfile {
    id: string;
    name: string;
    age: string;
    gender: string;
    blood_group: string;
    height: string | null;
    weight: string | null;
    photo: string | null;
    profile_type: string;
    patient_user_id: string;
}

export interface VaccinationSummary {
    completed_count: number;
    completed_percentage: number;
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