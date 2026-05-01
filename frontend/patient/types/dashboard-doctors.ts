export interface DashboardAvailableDoctor {
  id: string;
  name: string;
  speciality: string[];
  rating: number;
  years_experience: number;
  languages_known: string[];
  total_reviews: number;
  consultation_type: string;
  consultation_type_label: string[];
  consultation_fee: number;
  avatar: string;
}

export interface DoctorCardProps {
  doctor: DashboardAvailableDoctor;
  onBookNow?: (doctorId: string) => void;
}
