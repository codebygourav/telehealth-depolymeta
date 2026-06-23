

// <<<<<<< Updated upstream
// export type ApiDietTemplateMeal = {
//   id: string;
//   meal_type: string;
//   meal_name: string;
//   instructions?: string | null;
//   calories?: number | null;
//   protein_grams?: number | null;
//   carbs_grams?: number | null;
//   fat_grams?: number | null;
//   start_time?: string | null;
//   meal_time?: string | null;
//   status?: string;
//   patient_notes?: string | null;
//   completed_at?: string | null;
//   sort_order?: number | null;
// };

// export type ApiDietTemplateDay = {
//   id: string;
//   day_number: number;
//   week_day: string;
//   date?: string | null;
//   meals: ApiDietTemplateMeal[];
// };

// export type ApiDietTemplate = {
//   id: string;
//   doctor_id: string;
//   name: string;
//   description?: string | null;
//   duration_days: number;
//   restrictions?: string | null;
//   notes?: string | null;
//   is_active: boolean;
//   days: ApiDietTemplateDay[];
//   created_at?: string;
//   updated_at?: string;
// };

// export type DietTemplateListResponse = {
//   success: boolean;
//   data: ApiDietTemplate[];
//   meta?: {
//     current_page?: number;
//     per_page?: number;
//     total?: number;
//   };
// };

// export type AssignDietTemplatePayload = {
//   patient_id: string;
//   template_id: string;
//   start_date: string;
//   duration_days?: number;
//   special_instructions?: string;
// };

// export type PatientDietPlan = {
//   id: string;
//   patient_id: string;
//   doctor_id: string;
//   template_id: string;
//   template_name: string;
//   template_description?: string | null;
//   duration_days: number;
//   start_date: string;
//   end_date: string;
//   status: string;
//   special_instructions?: string | null;
//   days: ApiDietTemplateDay[];
// };

// export type AssignDietTemplateResponse = {
//   success: boolean;
//   data: PatientDietPlan;
//   message?: string;
// };
// =======

export type AssignDietTemplatePayload = {
  patient_id: string;
  template_id: string;
  start_date: string;
  duration_days: number;
  special_instructions?: string;
};

export type AssignDietTemplateResponse = {
  success: boolean;
  data: any;
  message?: string;
};

export interface DietTemplate {
    id: string;
    name: string;
    description?: string;
    created_at?: string;
    updated_at?: string;
    items?: any[];
}

export interface GetDietTemplatesResponse {
    success: boolean;
    message?: string;
    data: DietTemplate[];


}

