import type { Advertisement, PatientHomeData } from "@/types/home";
import type {
  HomeScreenAppointment,
  HomeScreenDepartmentCard,
  HomeScreenTestimonial,
} from "@/types/home-screen";

export function mapHomeScreenAppointments(
  homeData?: PatientHomeData
): HomeScreenAppointment[] {
  return (homeData?.upcoming_appointments ?? []).map((appt) => ({
    id: appt.appointment_id,
    doctorId: appt.doctor.user_id,
    doctorName: appt.doctor.name,
    doctorImage: appt.doctor.avatar,
    date: appt.appointment_date_formatted,
    time: appt.appointment_time_formatted,
    type: appt.consultation_type,
    typeLabel: appt.consultation_type_label,
    joinUrl: appt.video_consultation?.join_url,
    doctor: {
      specialty: appt.doctor.department,
      experience: appt.doctor.years_experience,
      languages: appt.doctor.languages_known,
    },
  }));
}

export function mapHomeScreenDepartmentCards(
  homeData?: PatientHomeData
): HomeScreenDepartmentCard[] {
  const departments = homeData?.speciality_symptoms ?? [];
  const uniqueDepartments = new Map<string, HomeScreenDepartmentCard>();

  for (const item of departments) {
    const slug = item.department.name.toLowerCase().replace(/\s+/g, "-");
    const existing = uniqueDepartments.get(slug);
    const symptoms = item.symptoms.map((symptom) => symptom.name);

    if (existing) {
      existing.symptoms = Array.from(new Set([...existing.symptoms, ...symptoms]));
      continue;
    }

    uniqueDepartments.set(slug, {
      id: item.id,
      name: item.department.name,
      icon: item.department.icon,
      stamp: item.department.stamp,
      symptoms,
      href: `/find-doctors?specialty=${slug}`,
    });
  }

  return Array.from(uniqueDepartments.values());
}

export function mapHomeScreenAdvertisements(
  homeData?: PatientHomeData
): Advertisement[] {
  return (homeData?.advertisements ?? []).map((ad) => ({
    id: ad.id,
    title: ad.title,
    description: ad.description,
    image: ad.image,
    link: ad.link,
  }));
}

export function mapHomeScreenTestimonials(
  homeData?: PatientHomeData
): HomeScreenTestimonial[] {
  return (homeData?.patient_reviews ?? []).map((review) => ({
    id: review.id,
    name: review.patient_name,
    location: review.patient_location,
    patientImage: review.patient_image,
    rating: review.rating,
    subject: review.title,
    feedback: review.content,
    reviewCount: review.total_reviews,
    doctorName: review.doctor_name,
    doctorImage: review.doctor_avatar,
    time: review.created_at,
  }));
}
