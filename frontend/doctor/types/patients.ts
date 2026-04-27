export interface PatientTotalAppointment {
  clinic_visit: number;
  video_consultation: number;
}

export interface PatientInfo {
  id: string;
  patient_id: string;
  user_id: string;
  slug: string;
  name: string;
  avatar: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  pincode: string | null;
  area: string | null;
  city: string | null;
  state: string | null;
  landmark: string | null;
  total_appointment: PatientTotalAppointment;
}

export interface PatientAppointmentRow {
  appointment_id: string;
  appointment_date: string;
  appointment_date_formatted: string;
  appointment_time_formatted: string;
  appointment_end_time_formatted: string;
  consultation_type: string;
  consultation_type_label: string;
  status: string;
  status_label: string;
  fee_amount: string;
  notes: string | null;
  opd_type?: string | null;
  patient: PatientInfo;
}

export interface PaginationMeta {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface GetAllPatientsResponse {
  success: boolean;
  message: string;
  pagination: PaginationMeta;
  path: string;
  timestamp: string;
  data: PatientAppointmentRow[];
}