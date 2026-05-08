import { Globe } from "lucide-react";
import { ProfileItemCard } from "./profileItemCard";

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

            <h2 className="text-[#1F1E1E] font-semibold text-lg mb-1.5">Social Media Links</h2>
            <p className="text-[#4D4D4D] text-sm">Your professional online presence</p>

            <div className="grid gap-4 xl:grid-cols-2">
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