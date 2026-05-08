import { Award } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { SectionHeader } from "./sectionHeader";
import { ProfileItemCard } from "./profileItemCard";

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

    return (
        <div className="space-y-4">

            <h2 className="text-[#1F1E1E] font-semibold text-lg mb-1.5">Awards & Recognition</h2>
            <p className="text-[#4D4D4D] text-sm">Your professional achievements</p>

            <div className="grid gap-4 xl:grid-cols-2">
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