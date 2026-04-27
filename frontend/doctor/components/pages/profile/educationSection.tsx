import { GraduationCap, MapPin, Plus } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { SectionHeader } from "./sectionHeader";
import { ProfileItemCard } from "./profileItemCard";

interface EducationSectionProps {

  // API shape may be wrapped (e.g. `{ education_info: [...] }`) or undefined.
  education?: any;
}

// export default function EducationSection({ education }: EducationSectionProps) {
//   const safeEducation: any[] = Array.isArray(
//     (education as any)?.education_info
//   )
//     ? (education as any).education_info
//     : [];
//   console.log("Doctor Education : ", safeEducation);

//   education?: any; // Marked as any to handle both array of EducationItem or object with education_info
// }

export default function EducationSection({ education }: EducationSectionProps) {
  // Handle both flat arrays and nested object structures
  const safeEducation = Array.isArray(education)
    ? education
    : Array.isArray(education?.education_info)
      ? education.education_info
      : [];

  return (
    <div className="space-y-4">
      <SectionHeader
        title="Education History"
        description="Your academic qualifications"
      // actionLabel="Add Education"
      // actionIcon={<Plus className="h-4 w-4 mr-2" />}
      />

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
        ))
        }
      </div >
    </div >
  );
}