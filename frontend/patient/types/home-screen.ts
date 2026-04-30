export interface HomeScreenAppointmentDoctor {
  specialty?: string | null;
  experience?: string;
  languages?: string[];
}

export interface HomeScreenAppointment {
  id: string | number;
  doctorId: string | number;
  doctorName: string;
  doctorImage: string;
  date: string;
  time: string;
  type: string;
  typeLabel: string;
  joinUrl?: string;
  doctor?: HomeScreenAppointmentDoctor;
}

export interface HomeScreenDepartmentCard {
  id: string;
  name: string;
  icon: string;
  stamp: string;
  symptoms: string[];
  href: string;
}

export interface HomeScreenTestimonial {
  id: string | number;
  name: string;
  location: string;
  patientImage: string;
  rating?: number;
  subject: string;
  feedback: string;
  reviewCount: number;
  doctorName: string;
  doctorImage: string;
  time: string;
}
