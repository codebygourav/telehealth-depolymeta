export interface Prescription {
  appointment_id: string;
  doctor_name: string;
  medician_name: string;
  problem: string;
  medician_timings: string;
  pdf_url: string;
  instructions_by_doctor: string | null;
  next_visit_date: string | null;
}

export interface GetPrescriptionsResponse {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: Prescription[];
}

export interface MedicineDetail {
  number: number;
  prescription_id: string;
  name: string;
  type: string;
  frequency: string;
  frequencylabel: string;
  times: string;
  date: string;
  start_date: string;
  end_date: string;
  instructions: string[];
  dosage: string;
  meal: string;
  status: string;
  notes: string | null;
}

export interface MedicineDetailsData {
  pdf_url: string;
  medicines: MedicineDetail[];
  instructions_by_doctor: string;
  next_visit_date: string;
  doctor_name: string;
  appointment_id: string;
  doctor_id: string;
}

export interface MedicineDetailsResponse {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: MedicineDetailsData;
}


