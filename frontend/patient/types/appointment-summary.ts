export interface AppointmentSchedule {
  date: string;
  date_formatted: string;
  date_format: string;
  day_format: string;
  time: string;
  time_formatted: string;
  booking_type: string;
  consultation_type: string;
  consultation_type_label: string;
  opd_type: string | null;
}

export interface AppointmentPatient {
  id: string;
  name: string;
  first_name: string;
  last_name: string;
  age: number;
  age_formatted: string;
  gender: string;
  gender_formatted: string;
  avatar: string;
  phone: string;
  email: string;
  blood_group: string | null;
  problem: string | null;
}

export interface AppointmentDoctor {
  id: string;
  user_id: string;
  name: string;
  first_name: string;
  last_name: string;
  avatar: string;
  years_experience: string;
  department: string;
  review: unknown[];
  total_reviews: number;
  average_rating?: number;
}

export interface AppointmentPayment {
  id: string;
  order_id: string;
  payment_id: string | null;
  key_id: string | null;
  consultation_fee: string;
  consultation_fee_formatted: string;
  admin_fee: string;
  admin_fee_formatted: string;
  discount: number;
  discount_formatted: string;
  total: number;
  total_formatted: string;
  status: string;
  status_label: string;
  payment_method: string | null;
  transaction_id: string | null;
}

export interface AppointmentMedicalReport {
  id?: string;
  title?: string;
  type?: string;
  type_label?: string;
  report_date?: string;
  report_date_formatted?: string;
  status?: string;
  file_url?: string;
  doctor_name?: string;
  files?: {
    url: string;
    name: string;
    type: string;
  }[] | null;
}

export interface AppointmentPrescription {
  id?: string;
  [key: string]: unknown;
}

export interface AppointmentDetailData {
  appointment_id: string;
  schedule: AppointmentSchedule;
  status: string;
  status_label: string;
  can_start_consultation: boolean;
  can_cancel: boolean;
  can_reschedule: boolean;
  call_now: boolean;
  join_url: string;
  patient: AppointmentPatient;
  doctor: AppointmentDoctor;
  can_add_review: boolean;
  razorpay_key_id: string;
  razorpay_order_id: string;
  payment: AppointmentPayment;
  medical_reports: AppointmentMedicalReport[];
  prescriptions: AppointmentPrescription | null;
  notes: string | null;
}

export interface AppointmentDetailResponse {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: AppointmentDetailData;
}