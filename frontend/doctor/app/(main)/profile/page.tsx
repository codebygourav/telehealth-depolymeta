"use client";

import AddressSection from "@/components/pages/profile/addressSection";
import AiTrainingSection from "@/components/pages/profile/aiTrainingSection";
import AwardsSection from "@/components/pages/profile/awardsSection";
import CertificatesSection from "@/components/pages/profile/certificatesSection";
import EducationSection from "@/components/pages/profile/educationSection";
import ExperienceSection from "@/components/pages/profile/experienceSection";
import PersonalInfoSection from "@/components/pages/profile/personalInfoSection";
import ProfileHeader from "@/components/pages/profile/profileHeader";
import ReviewsSection from "@/components/pages/profile/reviewsSection";
import SocialLinksSection from "@/components/pages/profile/socialLinksSection";
import VoiceSettingsSection from "@/components/pages/profile/voiceSettingsSection";
import { Button } from "@/components/ui";
import HeroSection from "@/components/ui/hero-section";
import { useAuth } from "@/context/userContext";
import { cn } from "@/lib/utils";
import { useDoctorHome } from "@/queries/useHome";
import { useDoctorProfile } from "@/queries/useProfile";
import { Award, BrainCircuit, FileBadge, GraduationCap, Link, MapPinPen, Trophy, User, UserStar, Volume2 } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

type TabKey = "personal" | "address" | "experience" | "education" | "awards" | "certificates" | "social" | "reviews" | "voice" | "ai-training";

const ProfilePage = () => {

    const [activeTab, setActiveTab] = useState<TabKey>("personal");
    const [isEditingPersonal, setIsEditingPersonal] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const { user } = useAuth();
    const { data, isLoading, isError, error } = useDoctorProfile();
    const { data: homeData } = useDoctorHome();

    const profile = data?.data;
    const homeProfile = homeData?.data;
    const personalInfo = profile?.personal_information;
    const address = profile?.address;
    const workingExperience = profile?.working_experience ?? [];
    const educationInfo = profile?.education_info ?? [];
    const certificationsInfo = profile?.certifications_info ?? [];
    const awardsInfo = profile?.awards_info ?? [];
    const socialMedia = profile?.social_media ?? null;
    const reviewSummary = profile?.review_summary ?? undefined;

    const sidebarItems = [
        {
            key: "personal" as TabKey,
            label: "Personal Info",
            icon: User,
        },
        {
            key: "address" as TabKey,
            label: "Manage Address",
            icon: MapPinPen,
        },
        {
            key: "experience" as TabKey,
            label: "Experience",
            icon: Award,
        },
        {
            key: "education" as TabKey,
            label: "Education",
            icon: GraduationCap,
        },
        {
            key: "awards" as TabKey,
            label: "Awards",
            icon: Trophy,
        },
        {
            key: "certificates" as TabKey,
            label: "Certificates",
            icon: FileBadge,
        },
        {
            key: "social" as TabKey,
            label: "Social Links",
            icon: Link,
        },
        {
            key: "reviews" as TabKey,
            label: "Reviews",
            icon: UserStar,
        },
        {
            key: "voice" as TabKey,
            label: "Voice Settings",
            icon: Volume2,
        },
        {
            key: "ai-training" as TabKey,
            label: "AI Training",
            icon: BrainCircuit,
        },
    ];

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
    const primaryDepartment = personalInfo?.doctor_departments?.[0]?.department_name || "General Practice";
    const primaryRole = personalInfo?.doctor_departments?.[0]?.role || "Doctor";

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
        <div className="container-max-width w-full mx-auto">

            <HeroSection title="Profile" description="Edit your profile information and manage your account" />

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

            <div className="flex flex-col md:flex-row gap-x-5 container-max-width w-full mx-auto my-7">

                {/* Sidebar */}
                <aside className="w-full md:w-72 lg:w-96 space-y-2">
                    <div className="bg-white p-3 lg:p-5 rounded-lg border-light-gray h-full">
                        {sidebarItems.map((item) => {
                            const Icon = item.icon;
                            const isActive = activeTab === item.key;
                            return (
                                <Button
                                    key={item.key}
                                    variant="outline"
                                    onClick={() => setActiveTab(item.key)}
                                    className={cn(
                                        "w-full flex items-center justify-start gap-3 px-5 py-3 h-auto text-start hover:bg-primary/10",
                                        isActive
                                            ? "text-primary"
                                            : "g-text-dark border border-transparent"
                                    )}
                                >
                                    <Icon size={14} color={`${isActive ? "var(--primary)" : "#4D4D4D"}`} />
                                    {item.label}
                                </Button>
                            );
                        })}
                    </div>
                </aside>

                {/* Content Area */}
                <main className="flex-1 bg-white p-3 lg:p-5 rounded-lg border-light-gray min-h-[600px]">
                    {activeTab === "personal" && (
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
                    )}
                    {activeTab === "address" && (
                        <div>
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
                        </div>
                    )}
                    {activeTab === "experience" && (
                        <ExperienceSection experience={mappedExperience} />
                    )}
                    {activeTab === "education" && (
                        <EducationSection education={mappedEducation} />
                    )}
                    {activeTab === "awards" && (
                        <AwardsSection awards={mappedAwards} />
                    )}
                    {activeTab === "certificates" && (
                        <CertificatesSection certificates={mappedCertificates} />
                    )}
                    {activeTab === "social" && (
                        <SocialLinksSection socialMedia={mappedSocialMedia} />
                    )}
                    {activeTab === "reviews" && (
                        <ReviewsSection reviews={reviews} averageRating={reviewSummary?.average_rating?.toString() || "0"} />
                    )}
                    {activeTab === "voice" && (
                        <VoiceSettingsSection voiceSettings={profile?.voice_settings ?? null} userId={user?.id} />
                    )}
                    {activeTab === "ai-training" && (
                        <AiTrainingSection aiTraining={profile?.ai_training ?? null} userId={user?.id} />
                    )}
                </main>
            </div>

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