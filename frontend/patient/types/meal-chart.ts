export interface Meal {
  id: string;
  meal_type: string;
  meal_name: string;
  instructions: string | null;
  meal_image?: string | null;
  helpful_links?: Array<{
    type?: string | null;
    title?: string | null;
    url: string;
  }>;
  calories: number | null;
  protein_grams: number | null;
  carbs_grams: number | null;
  fat_grams: number | null;
  meal_time: string;
  status: string;
  patient_notes: string | null;
  completed_by_role?: string | null;
  completed_by_name?: string | null;
  completed_at: string | null;
  occurrence_date?: string;
  sort_order: number;
}

export interface DietPlanDay {
  id: string;
  day_number: number;
  week_day: string;
  date: string;
  meals: Meal[];
}

export interface DietPlanData {
  id: string;
  patient_id: string;
  doctor_id: string;
  doctor_name?: string;
  template_id: string;
  template_name: string;
  template_description: string;
  duration_days: number;
  start_date: string;
  end_date: string;
  status: string;
  special_instructions: string | null;
  restrictions?: string | null;
  diet_category?: string | null;
  patient_type?: string | null;
  daily_calories?: string | null;
  protein_target?: string | null;
  carbs_limit?: string | null;
  salt_limit?: string | null;
  doctor_remark?: string | null;
  allowed_food_notes?: string | null;
  hydration_advice?: string | null;
  exercise_advice?: string | null;
  features?: any;
  days: DietPlanDay[];
  created_at: string;
  updated_at: string;
}

export interface DietPlanResponse {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: {
    patient_id?: string;
    plans?: DietPlanData[];
  } | DietPlanData;
}
