import { GraduationCap } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { ProfileItemCard } from "./profileItemCard";

interface EducationSectionProps {
    education?: any;
}

export default function EducationSection({ education }: EducationSectionProps) {

    // Handle both flat arrays and nested object structures
    const safeEducation = Array.isArray(education)
        ? education
        : Array.isArray(education?.education_info)
            ? education.education_info
            : [];

    return (
        <div className="space-y-4">

            <h2 className="text-[#1F1E1E] font-semibold text-lg mb-1.5">Education History</h2>
            <p className="text-[#4D4D4D] text-sm">Your academic qualifications </p>

            <div className="space-y-4">
                {safeEducation?.map((edu: any, index: number) => (
                    <ProfileItemCard
                        key={index}
                        icon={<GraduationCap className="h-6 w-6" />}
                        title={edu.degree || edu.institute_name || "Unknown"}
                        subtitle={edu.institution || edu.institute_name}
                        meta={edu.location}
                        badge={<Badge variant="secondary">{edu.year || edu.passing_year}</Badge>}
                    />
                ))}
            </div>
        </div>
    );
}