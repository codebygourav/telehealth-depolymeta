// export type DoctorProfileGroup =
//   | "personal_information"
//   | "working_experience"
//   | "education_info"
//   | "certifications_info"
//   | "address"
//   | "social_media"
//   | "additional_information";

// export interface ApiResponse<T> {
//   success: boolean;
//   message: string;
//   group: DoctorProfileGroup;
//   path: string;
//   timestamp: string;
//   data: T;
// }

// export interface DoctorDepartment {
//   department_id: string;
//   department_name: string;
//   role: string;
// }

// export interface PersonalInformationData {
//   first_name: string;
//   last_name: string;
//   bio: string | null;
//   doctor_departments: DoctorDepartment[];
//   email: string;
//   avatar: string | null;
// }

// export interface WorkingExperienceItem {
//   career_start: number | null;
//   past_associations: string | null;
// }

// export interface WorkingExperienceData {
//   professional_experience_info: WorkingExperienceItem[];
// }

// export interface EducationItem {
//   degree: string | null;
//   institution: string | null;
//   start_date: string | null;
//   end_date: string | null;
// }

// export interface EducationData {
//   education_info: EducationItem[];
// }

// export interface CertificationItem {
//   name: string | null;
//   organization: string | null;
//   issue_date: string | null;
//   expiry_date: string | null;
//   certification_image: string | null;
// }

// export interface CertificationsData {
//   certifications_info: CertificationItem[];
// }

// export interface AddressData {
//   address_line1: string | null;
//   address_line2: string | null;
//   country: string | null;
//   state: string | null;
//   area: string | null;
//   city: string | null;
//   pincode: string | null;
//   landmark: string | null;
// }

// export interface SocialLinks {
//   facebook: string | null;
//   twitter: string | null;
//   linkedin: string | null;
//   instagram: string | null;
//   website: string | null;
// }

// export interface SocialMediaData {
//   social_links: SocialLinks;
// }

// export interface AdditionalInformationData {
//   special_interests: string | null;
//   availability_info: string | null;
//   memberships_info: string | null;
//   specializations_info: string | null;
//   key_procedures_info: string | null;
//   expertise_info: string | null;
// }

// export interface DoctorProfileGroupMap {
//   personal_information: PersonalInformationData;
//   working_experience: WorkingExperienceData;
//   education_info: EducationData;
//   certifications_info: CertificationsData;
//   address: AddressData;
//   social_media: SocialMediaData;
//   additional_information: AdditionalInformationData;
// }


export interface DoctorDepartment {
  department_id: string;
  department_name: string;
  role: string;
}

export interface PersonalInformation {
  first_name: string;
  last_name: string;
  bio: string | null;
  doctor_departments: DoctorDepartment[];
  email: string | null;
  avatar: string | null;
  medical_license: string | null;
}

export interface WorkingExperienceItem {
  career_start: number | null;
  past_associations: string | null;
}

export interface EducationItem {
  degree: string | null;
  institution: string | null;
  start_date: string | null;
  end_date: string | null;
}

export interface CertificationItem {
  name: string | null;
  organization: string | null;
  issue_date: string | null;
  expiry_date: string | null;
  certification_image: string | null;
}

export interface AddressInfo {
  address_line1: string | null;
  address_line2: string | null;
  country: string | null;
  state: string | null;
  area: string | null;
  city: string | null;
  pincode: string | null;
  landmark: string | null;
}

export interface AwardItem {
  award_image: string | null;
  title: string | null;
  organization: string | null;
  year: number | null;
  description: string | null;
}

export interface AdditionalInformation {
  special_interests: string | null;
  availability_info: string | null;
  memberships_info: string | null;
  specializations_info: string | null;
  key_procedures_info: string | null;
  expertise_info: string | null;
}

export interface SocialMedia {
  facebook: string | null;
  twitter: string | null;
  linkedin: string | null;
  instagram: string | null;
  website: string | null;
}

export interface ReviewSummary {
  average_rating: number;
  total_reviews: number;
}

export interface DoctorProfileData {
  personal_information: PersonalInformation;
  working_experience: WorkingExperienceItem[];
  education_info: EducationItem[];
  certifications_info: CertificationItem[];
  address: AddressInfo | null;
  awards_info: AwardItem[];
  fellowships_training: unknown[];
  additional_information: AdditionalInformation | null;
  social_media: SocialMedia | null;
  review_summary: ReviewSummary | null;
}

export interface GetDoctorProfileResponse {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: DoctorProfileData;
}