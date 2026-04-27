import { Award, Plus } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { SectionHeader } from "./sectionHeader";
import { ProfileItemCard } from "./profileItemCard";
import { ActionButtons } from "./actionButtons";


// interface AwardsSectionProps {
//   // API shape may be wrapped (e.g. `{ awards_info: [...] }`) or undefined.
//   awards?: any;
// }

// export default function AwardsSection({ awards }: AwardsSectionProps) {
//   const safeAwards: any[] = Array.isArray((awards as any)?.awards_info)
//     ? (awards as any).awards_info

interface AwardItem {
  award_image?: string;
  title?: string;
  organization?: string;
  year?: number | string;
  description?: string;
}

interface AwardsSectionProps {
  awards: {
    awards_info?: AwardItem[];
  } | any;
}

export default function AwardsSection({ awards }: AwardsSectionProps) {
  const safeAwards: AwardItem[] = Array.isArray(awards?.awards_info)
    ? awards.awards_info
    : Array.isArray(awards)
      ? awards
      : [];

  console.log("safeAwards : ", safeAwards);
  return (
    <div className="space-y-4">
      <SectionHeader
        title="Awards & Recognition"
        description="Your professional achievements"
      // actionLabel="Add Award"
      // actionIcon={<Plus className="h-4 w-4 mr-2" />}
      />

      <div className="grid gap-4 md:grid-cols-2">
        {safeAwards.map((award, index: number) => (
          <ProfileItemCard
            key={index}
            imageSrc={award.award_image}
            imageAlt={award.title || "Award image"}
            icon={<Award className="h-6 w-6" />}
            title={award.title || "Untitled Award"}
            subtitle={award.organization || "Organization not provided"}
            description={award.description || "No description available"}
            badge={<Badge variant="secondary">{award.year || "N/A"}</Badge>}
            iconClassName="bg-amber-50 text-amber-500"
            isView={true}
            viewUrl={award.award_image}
          />
        ))}
      </div>
    </div>
  );
}