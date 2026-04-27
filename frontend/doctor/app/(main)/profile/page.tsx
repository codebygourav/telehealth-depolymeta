"use client";

import { useEffect, useMemo, useState } from "react";
import CustomTabs from "@/components/pages/appoitment/CustomTabs";
import AddressSection from "@/components/pages/profile/addressSection";
import AwardsSection from "@/components/pages/profile/awardsSection";
import CertificatesSection from "@/components/pages/profile/certificatesSection";
import EducationSection from "@/components/pages/profile/educationSection";
import ExperienceSection from "@/components/pages/profile/experienceSection";
import PersonalInfoSection from "@/components/pages/profile/personalInfoSection";
import ProfileHeader from "@/components/pages/profile/profileHeader";
import ReviewsSection from "@/components/pages/profile/reviewsSection";
import SocialLinksSection from "@/components/pages/profile/socialLinksSection";
import { useDoctorProfile } from "@/queries/useProfile";
import { useDoctorHome } from "@/queries/useHome";
import { useAuth } from "@/context/userContext";

const ProfilePage = () => {
  const [activeTab, setActiveTab] = useState("personal");
  const [isEditingPersonal, setIsEditingPersonal] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  const { user } = useAuth();
  const { data, isLoading, isError, error } = useDoctorProfile();
  const { data: homeData } = useDoctorHome();

  const profile = data?.data;
  console.log("profile data : ", profile);
  const homeProfile = homeData?.data;

  const personalInfo = profile?.personal_information;
  const address = profile?.address;
  const workingExperience = profile?.working_experience ?? [];
  const educationInfo = profile?.education_info ?? [];
  const certificationsInfo = profile?.certifications_info ?? [];
  const awardsInfo = profile?.awards_info ?? [];
  const socialMedia = profile?.social_media ?? null;
  const reviewSummary = profile?.review_summary ?? undefined;

  const [formData, setFormData] = useState({
    first_name: "",
    last_name: "",
    email: "",
    bio: "",
    phone: "",
    medical_license: "",
  });

  useEffect(() => {
    if (personalInfo) {
      setFormData({
        first_name: personalInfo.first_name ?? "",
        last_name: personalInfo.last_name ?? "",
        email: personalInfo.email ?? "",
        bio: personalInfo.bio ?? "",
        phone: user?.phone ?? "",
        medical_license: personalInfo.medical_license ?? "",
      });
    }
  }, [personalInfo, user]);

  // Also update cancel handler when user changes
  useEffect(() => {
    if (!isEditingPersonal) {
      setFormData((prev) => ({
        ...prev,
        phone: user?.phone ?? "",
      }));
    }
  }, [user?.phone, isEditingPersonal]);

  const fullName = `${formData.first_name} ${formData.last_name}`.trim() || "Doctor";

  const initials = `${formData.first_name?.[0] ?? ""}${formData.last_name?.[0] ?? ""}` || "DR";

  const primaryDepartment =
    personalInfo?.doctor_departments?.[0]?.department_name || "General Practice";

  const primaryRole =
    personalInfo?.doctor_departments?.[0]?.role || "Doctor";

  const mappedExperience = useMemo(() => {
    return workingExperience.map((item, index) => ({
      id: index + 1,
      company: item.past_associations || "N/A",
      role: "Doctor",
      period: item.career_start ? `${item.career_start} - Present` : "N/A",
    }));
  }, [workingExperience]);

  const mappedEducation = useMemo(() => {
    return educationInfo.map((item, index) => ({
      id: index + 1,
      degree: item.degree || "N/A",
      institution: item.institution || "N/A",
      year: formatEducationYear(item.start_date, item.end_date),
    }));
  }, [educationInfo]);

  const mappedCertificates = useMemo(() => {
    return certificationsInfo
      .filter(
        (item) =>
          item.name ||
          item.organization ||
          item.issue_date ||
          item.expiry_date ||
          item.certification_image
      )
      .map((item, index) => ({
        id: index + 1,
        name: item.name || "N/A",
        organization: item.organization || "N/A",
        issue_date: item.issue_date || "",
        expiry_date: item.expiry_date || "",
        certification_image: item.certification_image || "",
      }));
  }, [certificationsInfo]);

  const mappedAwards = useMemo(() => {
    return {
      awards_info: awardsInfo.map((item) => ({
        award_image: item.award_image || "",
        title: item.title || "N/A",
        organization: item.organization || "N/A",
        year: item.year || "",
        description: item.description || "",
      })),
    };
  }, [awardsInfo]);

  const mappedSocialMedia = useMemo(() => {
    return {
      facebook: socialMedia?.facebook || "",
      twitter: socialMedia?.twitter || "",
      linkedin: socialMedia?.linkedin || "",
      instagram: socialMedia?.instagram || "",
      website: socialMedia?.website || "",
    };
  }, [socialMedia]);

  const reviews = useMemo(() => {
    return homeProfile?.doctor_reviews?.map((review, index) => ({
      id: parseInt(review.id) || index + 1,
      patient: review.patient_name,
      rating: review.rating,
      date: review.created_at,
      comment: review.content,
      patient_image: review.patient_image,
    })) || [];
  }, [homeProfile?.doctor_reviews]);

  const handleInputChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSavePersonalInfo = async () => {
    setIsSaving(true);
    try {
      // later connect update profile API here
      await new Promise((resolve) => setTimeout(resolve, 1000));
      setIsEditingPersonal(false);
    } catch (error) {
      console.error("Error saving personal info:", error);
    } finally {
      setIsSaving(false);
    }
  };

  const handleCancelPersonalEdit = () => {
    setIsEditingPersonal(false);

    setFormData({
      first_name: personalInfo?.first_name ?? "",
      last_name: personalInfo?.last_name ?? "",
      email: personalInfo?.email ?? "",
      bio: personalInfo?.bio ?? "",
      phone: user?.phone ?? "",
      medical_license: personalInfo?.medical_license ?? "",
    });
  };

  const tabs = [
    {
      key: "personal",
      label: "Personal Info",
      content: (
        <PersonalInfoSection
          isEditing={isEditingPersonal}
          setIsEditing={setIsEditingPersonal}
          formData={formData}
          profileData={{
            email: personalInfo?.email ?? "",
            bio: personalInfo?.bio ?? "",
            avatar: personalInfo?.avatar ?? "",
            doctor_departments: personalInfo?.doctor_departments ?? [],
          }}
          fullName={fullName}
          primaryDepartment={primaryDepartment}
          primaryRole={primaryRole}
          isSaving={isSaving}
          onInputChange={handleInputChange}
          onSave={handleSavePersonalInfo}
          onCancel={handleCancelPersonalEdit}
          averageRating={reviewSummary}
        />
      ),
    },
    {
      key: "address",
      label: "Address",
      content: (
        <AddressSection
          address={{
            address_line1: address?.address_line1 ?? "",
            address_line2: address?.address_line2 ?? "",
            area: address?.area ?? "",
            landmark: address?.landmark ?? "",
            city: address?.city ?? "",
            state: address?.state ?? "",
            country: address?.country ?? "",
            pincode: address?.pincode ?? "",
          }}
        />
      ),
    },
    {
      key: "experience",
      label: "Experience",
      content: <ExperienceSection experience={mappedExperience} />,
    },
    {
      key: "education",
      label: "Education",
      content: <EducationSection education={mappedEducation} />,
    },
    {
      key: "awards",
      label: "Awards",
      content: <AwardsSection awards={mappedAwards} />,
    },
    {
      key: "certificates",
      label: "Certificates",
      content: <CertificatesSection certificates={mappedCertificates} />,
    },
    {
      key: "social",
      label: "Social Links",
      content: <SocialLinksSection socialMedia={mappedSocialMedia} />,
    },
    {
      key: "reviews",
      label: "Reviews",
      content: (
        <ReviewsSection reviews={reviews} averageRating={reviewSummary?.average_rating?.toString() || "0"} />
      ),
    },
  ];

  if (isLoading) {
    return (
      <div className="flex min-h-[300px] items-center justify-center">
        <p className="text-sm text-muted-foreground">Loading profile...</p>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="flex min-h-[300px] items-center justify-center">
        <p className="text-sm text-red-500">
          {getErrorMessage(error)}
        </p>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4 md:px-4">
      <ProfileHeader
        fullName={fullName}
        avatar={personalInfo?.avatar ?? ""}
        initials={initials}
        department={primaryDepartment}
        role={primaryRole}
        email={personalInfo?.email ?? ""}
        phone={formData.phone}
        license={formData.medical_license}
        reviewSummary={reviewSummary}
      />

      <CustomTabs
        tabs={tabs}
        activeTab={activeTab}
        onTabChange={setActiveTab}
        tabsListClassName="w-full overflow-x-auto overflow-y-hidden scrollbar-hide flex-nowrap justify-start sm:justify-start md:justify-start lg:justify-start"
      />
    </div>
  );
};

export default ProfilePage;

function formatEducationYear(
  startDate?: string | null,
  endDate?: string | null
) {
  if (!startDate && !endDate) return "N/A";

  const startYear = startDate ? new Date(startDate).getFullYear() : "";
  const endYear = endDate ? new Date(endDate).getFullYear() : "";

  if (startYear && endYear) return `${startYear} - ${endYear}`;
  if (startYear) return `${startYear}`;
  if (endYear) return `${endYear}`;

  return "N/A";
}

function getErrorMessage(error: unknown) {
  if (
    typeof error === "object" &&
    error !== null &&
    "response" in error &&
    typeof (error as any).response?.data?.message === "string"
  ) {
    return (error as any).response.data.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Failed to load profile.";
}