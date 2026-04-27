export interface DashboardSummary {
  todays_appointments: number;
  upcoming_appointments: number;
  cancelled_appointments: number;
  average_review_score: number;
}

export interface DashboardAppointment {
  id: string;
  patient_name: string;
  patient_image: string | null;
  time: string;
  date: string;
  consultation_type: string;
  status: string;
  call_now?: boolean;
}

export interface DoctorReview {
  id: string;
  patient_name: string;
  patient_image: string | null;
  patient_age: string;
  patient_location: string | null;
  title: string;
  content: string;
  rating: number;
  total_reviews: number;
  doctor_name: string;
  doctor_avatar: string | null;
  doctor_experience: string;
  doctor_departments: string;
  rating_stars: string;
  created_at: string;
}

export interface ReviewSummary {
  average_rating: number;
  total_reviews: number;
}

export interface DoctorHomeData {
  id: string;
  name: string;
  first_name: string;
  last_name: string;
  slug: string;
  avatar: string | null;
  location: string | null;
  doctor: {
    id: string;
    name: string;
    first_name: string;
    last_name: string;
    slug: string;
    avatar: string | null;
    location: null;
  };
  summary: DashboardSummary;
  todays_appointments: DashboardAppointment[];
  upcoming_appointments: DashboardAppointment[];
  doctor_reviews: DoctorReview[];
  review_summary: ReviewSummary;
}

export interface GetDoctorHomeResponse {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: DoctorHomeData;
}