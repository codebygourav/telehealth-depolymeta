export interface Doctor {
  id: string;
  name: string;
  speciality: string | { id: string; name: string; symptoms?: any[] };
  avatar: string;
  rating: number;
  years_experience: string;
  languages_known: string[];
  consultation_fee: number;
  consultation_type_label: string[];
  hospital?: string;
  availability?: string;
}

export interface BrowseDoctorsResponse {
  data: Doctor[];
  success: boolean;
  message?: string;
}

export type ConsultationType = 'video' | 'in-person' | 'all';
export type SortOption = 'highest-rated' | 'price-low-high' | 'next-available';
export type Page = 'appointments' | 'medical-records' | 'find-doctors' | 'doctor-profile';