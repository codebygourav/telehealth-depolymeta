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
        recommended_age_label: string;
        set_name: string;
        vaccination: {
            id: string;
            name: string;
            short_name: string;
            manufacturer?: string;
        };
    }[];
};