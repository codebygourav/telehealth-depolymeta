export interface DoctorProfileInfo {
  name: string;
  avatar: string;
  department: string;
  years_experience: number;
}

export interface DoctorAboutInfo {
  bio: string;
  description: string;
}

export interface DoctorEducationItem {
  degree: string;
  institution: string;
  start_date: string;
  end_date: string;
}

export interface DoctorAppointmentTypes {
  in_person: boolean;
  video: boolean;
}

export interface DoctorReviewItem {
  id: string;
  patient_name: string;
  patient_image: string;
  patient_age: string;
  patient_location: string | null;
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

export interface DoctorAvailabilitySlot {
  id: string;
  date: string;
  day_of_week: string;
  booking_start_time: string;
  start_time: string;
  end_time: string;
  consultation_type: string;
  consultation_type_label: string;
  capacity: number;
  booked_count: number;
  available: boolean;
  consultation_fee: number;
  doctor_room: string | null;
  recurring_start_date: string;
  recurring_end_date: string;
}

export interface DoctorAvailabilityItem {
  date: string;
  slots: DoctorAvailabilitySlot[];
}

export interface DoctorReviewSummary {
  average_rating: number;
  total_reviews: number;
}

export interface DoctorDetailData {
  id: string;
  slug: string;
  user_id: string;
  status: string;
  profile: DoctorProfileInfo;
  about: DoctorAboutInfo;
  education: DoctorEducationItem[];
  languages: string[];
  appointment_types: DoctorAppointmentTypes;
  doctor_reviews: DoctorReviewItem[];
  availability: DoctorAvailabilityItem[];
  review_summary: DoctorReviewSummary;
}

export interface DoctorDetailResponse {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: DoctorDetailData;
}