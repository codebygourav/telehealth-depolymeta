"use client";

import { FullWidthDashboardSection } from "@/components/pages/Dashboard/FullWidthDashboardSection";
import { SectionHeader } from "@/components/custom/SectionHeader";
import { DashboardCarousel } from "@/components/pages/Dashboard/dashboard-carousel";

export interface Advertisement {
    id: string;
    title: string;
    description: string;
    image: string;
    link?: string;
}

interface AdvertisementsProps {
    ads: Advertisement[];
}

export function Advertisements({ ads }: AdvertisementsProps) {
    if (!ads || ads.length === 0) {
        return null;
    }

    const AdvertisementCard = ({ ad }: { ad: Advertisement }) => {
        return (
            <div className="p-5">
                {ad.link && ad.link !== "#" ? (
                    <a href={ad.link} target="_blank" rel="noopener noreferrer">
                        <img
                            src={ad.image}
                            alt={ad.title}
                            className="object-cover w-full"
                            loading="lazy"
                        />
                    </a>
                ) : (
                    <img
                        src={ad.image}
                        alt={ad.title}
                        className="object-cover w-full"
                        loading="lazy"
                    />
                )}
            </div>

        );
    };

    return (
        <FullWidthDashboardSection className="pt-9 pb-9 bg-[#f5f6f8]">
            <SectionHeader
                title="Safe & Advanced Surgical Care"
                subtitle="With Super specialist doctors and state-of-the-art technology, we cover the complete spectrum of medical specialties"
                headingClassName="text-black"
                subtitleClassName="text-black"
                containerClassName="mb-2 px-5 sm:px-5"
            />
            <DashboardCarousel
                items={ads}
                contentClassName="-ml-3 sm:-ml-4 md:-ml-6 py-3 sm:py-4 px-1"
                basisClassName="pl-3 sm:pl-4 md:pl-6 basis-full sm:basis-1/2 lg:basis-1/3"
                dotClassName="bg-white/35 opacity-100 hover:bg-white/55"
                activeDotClassName="bg-white opacity-100  z-10"
                renderItem={(ad) => <AdvertisementCard key={ad.id} ad={ad} />}
            />
        </FullWidthDashboardSection>
    );
}
