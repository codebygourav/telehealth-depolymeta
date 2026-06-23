export type MedicineTemplateItem = {
  id: string;
  medicine_id?: string | null;
  medicine_name: string;
  medicine_type?: string | null;
  dosage?: string | null;
  doses_per_day?: number | null;
  first_dose_time?: string | null;
  dose_interval_hours?: number | null;
  frequency: "OD" | "BD" | "TDS" | "SOS" | string;
  frequency_times?: string[];
  meal_timing?: string | null;
  duration_type?: "days" | "weeks" | "months" | string;
  duration_value?: number | null;
  instructions?: string | null;
  sort_order?: number;
};

export type MedicineTemplate = {
  id: string;
  scope_type?: "global" | "doctor" | "department" | string;
  department_id?: string | null;
  doctor_id?: string | null;
  name: string;
  description?: string | null;
  is_active: boolean;
  items: MedicineTemplateItem[];
  created_at?: string;
  updated_at?: string;
};

export type GetMedicineTemplatesResponse = {
  success: boolean;
  data: MedicineTemplate[];
  meta?: {
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
  };
};

export type AssignMedicineTemplatePayload = {
  template_id: string;
  start_date?: string;
  stamp_preference?: "only_department" | "both" | "only_global";
};
