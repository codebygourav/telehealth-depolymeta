export interface MedicineItem {
  id: string;
  name: string;
  type: string | null;
  category: string | null;
  source?: "inventory" | "doctor_added";
  strength_options?: string[];
  dosage_options?: string[];
  frequency_options?: string[];
  timing_options?: string[];
  meal_options?: string[];
  duration_options?: string[];
  application_area_options?: string[];
  field_rules?: string[];
  spoken_aliases?: string[];
  created_at: string;
  updated_at: string;
}

export interface MedicinesPagination {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface GetMedicinesResponse {
  success: boolean;
  message: string;
  pagination: MedicinesPagination;
  path: string;
  timestamp: string;
  data: MedicineItem[];
}
