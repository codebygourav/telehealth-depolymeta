// ─── Advertisements ──────────────────────────────────────────────────────────
export interface Advertisement {
  id: string;
  title: string;
  description: string;
  image: string;
  link: string;
}

// ─── Available Doctors ────────────────────────────────────────────────────────
export interface AvailableDoctor {
  id: string;
  name: string;
  speciality: string[];
  rating: number;
  years_experience: number;
  languages_known: string[];
  total_reviews: number;
  consultation_type: string;
  consultation_fee: number;
  avatar: string;
}

// ─── Patient Reviews ──────────────────────────────────────────────────────────
export interface PatientReview {
  id: string;
  patient_name: string;
  patient_image: string;
  patient_age: string;
  patient_location: string;
  title: string;
  content: string;
  rating: number;
  total_reviews: number;
  doctor_name: string;
  doctor_avatar: string;
  doctor_experience: string;
  doctor_departments: string;
  rating_stars: string;
  created_at: string;
}

// ─── Speciality & Symptoms ────────────────────────────────────────────────────
export interface Symptom {
  name: string;
  icon: string;
}

export interface Department {
  name: string;
  icon: string;
  stamp: string;
}

export interface SpecialitySymptom {
  id: string;
  department: Department;
  symptoms: Symptom[];
}

// ─── Upcoming Appointments ────────────────────────────────────────────────────
export interface AppointmentDoctor {
  id: string;
  user_id: string;
  name: string;
  avatar: string;
  department: string;
  slug: string;
  years_experience: string;
  average_rating: number;
  languages_known: string[];
}

export interface UpcomingAppointment {
  appointment_id: string;
  appointment_date: string;
  appointment_date_formatted: string;
  appointment_time: string;
  appointment_end_time: string;
  appointment_time_formatted: string;
  appointment_end_time_formatted: string;
  consultation_type: string;
  consultation_type_label: string;
  video_consultation?: {
    id: string;
    room_id: string;
    status: string;
    join_url: string;
    can_join: boolean;
    started_at: string;
    ended_at: string;
  };
  status: string;
  status_label: string;
  fee_amount: string;
  call_now: boolean;
  notes: string;
  opd_type: string;
  doctor: AppointmentDoctor;
}

// ─── Full Home Data ───────────────────────────────────────────────────────────
export interface PatientHomeData {
  advertisements: Advertisement[];
  available_doctors: AvailableDoctor[];
  patient_reviews: PatientReview[];
  speciality_symptoms: SpecialitySymptom[];
  upcoming_appointments: UpcomingAppointment[];
}

// ─── API Response ─────────────────────────────────────────────────────────────
export interface GetPatientHomeResponse {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: PatientHomeData;
}

// ─── UI Mapped Types ─────────────────────────────────────────────────────────
export interface MappedAppointmentDoctor {
  specialty?: string;
  experience?: string;
  languages?: string[];
}

export interface MappedAppointment {
  id: string | number;
  doctorId: string | number;
  doctorName: string;
  doctorImage: string;
  date: string;
  time: string;
  type: string;
  typeLabel: string;
  doctor?: MappedAppointmentDoctor;
}
