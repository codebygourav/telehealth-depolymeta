export interface VaccinationTemplate {
    id: string;
    name: string;
    ageGroup?: string;
    totalVaccines?: number;
    lastUpdated?: string;
}

export interface GetVaccinationTemplatesResponse {
    success: boolean;
    data: ApiVaccinationTemplate[];
}

export type ApiVaccinationTemplate = {
    id: string;
    name: string;
    description: string;
    created_at: string;
    items: {
        id: string;
        dose_no: number;
        timing_type?: 'base_date' | 'previous_dose' | 'doctor_manual_date';
        offset_value?: number;
        offset_unit?: 'days' | 'weeks' | 'months' | 'years';
        interval_value?: number;
        interval_unit?: 'days' | 'weeks' | 'months' | 'years';
        doctor_manual_date?: boolean;
        depends_on_previous_dose?: boolean;
        recommended_age_label: string;
        due_after_days?: number;
        due_after_months?: number;
        minimum_age_days?: number;
        maximum_age_days?: number;
        set_name: string;
        vaccination: {
            id: string;
            name: string;
            short_name: string;
            manufacturer?: string;
        };
    }[];
};
