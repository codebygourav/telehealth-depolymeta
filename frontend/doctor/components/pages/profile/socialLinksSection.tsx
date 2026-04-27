import { Globe, Plus } from "lucide-react";
import { SectionHeader } from "./sectionHeader";
import { ProfileItemCard } from "./profileItemCard";
import { ActionButtons } from "./actionButtons";

// interface SocialItem {
//   id?: string | number;
//   platform?: string;
//   url?: string;
// }

// interface SocialLinksSectionProps {
//   // API shape may be wrapped (e.g. `{ social_media: [...] }`) or undefined.
//   socialMedia?: any;
// }

// export default function SocialLinksSection({ socialMedia }: SocialLinksSectionProps) {
//   const safeSocialMedia: SocialItem[] = Array.isArray(
//     socialMedia?.social_media
//   )
//     ? socialMedia.social_media

interface SocialLinks {
  facebook?: string;
  twitter?: string;
  linkedin?: string;
  instagram?: string;
  website?: string;
  [key: string]: string | undefined;
}

interface SocialLinksSectionProps {
  socialMedia?: SocialLinks;
}

export default function SocialLinksSection({
  socialMedia,
}: SocialLinksSectionProps) {

  const safeSocialMedia = socialMedia
    ? Object.entries(socialMedia)
      .filter(([, url]) => !!url)
      .map(([platform, url], index) => ({
        id: index + 1,
        platform,
        url: url as string,
      }))
    : [];

  return (
    <div className="space-y-4">
      <SectionHeader
        title="Social Media Links"
        description="Your professional online presence"
      // actionLabel="Add Link"
      // actionIcon={<Plus className="h-4 w-4 mr-2" />}
      />

      <div className="grid gap-4 md:grid-cols-2">
        {/* {safeSocialMedia.length === 0 ? (
          <p className="text-center text-muted-foreground col-span-2">No social media found</p>
        ) : (
          safeSocialMedia.map((social, index) => (
          <ProfileItemCard
            key={social.id ?? index}
            icon={<Globe className="h-6 w-6" />}
            title={social.platform ?? ""}
            subtitle={social.url ?? ""}
            actions={<ActionButtons />}
          />
        ))
      )} */}
        {safeSocialMedia.length > 0 ? (
          safeSocialMedia.map((social) => (
              <ProfileItemCard
                key={social.id}
                icon={<Globe className="h-6 w-6" />}
                title={
                  social.platform.charAt(0).toUpperCase() +
                  social.platform.slice(1)
                }
                subtitle={social.url}
                isView={true}
                viewUrl={social.url}
              />
          ))
        ) : (
          <p className="text-sm text-muted-foreground">
            No social media links available.
          </p>
        )}
      </div>
    </div>
  );
}