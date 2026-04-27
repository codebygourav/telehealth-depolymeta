export interface MedicineItem {
  id: string;
  name: string;
  type: string;
  category: string;
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