"use client";

import { FullWidthDashboardSection } from "@/components/pages/Dashboard/FullWidthDashboardSection";
import { SectionHeader } from "@/components/custom/SectionHeader";
import { DashboardCarousel } from "@/components/pages/Dashboard/dashboard-carousel";
import { Button, Card, CardContent } from "@/components/ui";
import { ExternalLink } from "lucide-react";

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
            <Card className="rounded-[5px] shadow-card-lg py-0">
                <CardContent className="flex-1 py-4">
                        <img
                            src={ad.image}
                            alt={ad.title}
                            className="w-full h-[400px] sm:h-[400px] object-cover"
                            loading="lazy"
                        />

                    {ad.link && ad.link !== "#" ? (
                    <div className="flex items-end justify-between w-full gap-2 mt-auto sm:gap-3">
                       
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="rounded-[5px] border-gray-300 bg-white text-[#103228] hover:bg-gray-100 hover:text-[#103228] shrink-0"
                            >
                                <a href={ad.link} target="_blank" rel="noopener noreferrer">
                                    <ExternalLink className="mr-2" />
                                    View
                                </a>
                            </Button>
                        
                    </div>
                    ) : null}
                </CardContent>
            </Card>
        );
    };

    return (
        <FullWidthDashboardSection className="pt-9 pb-9 bg-primary">
            <SectionHeader
                title="Safe & Advanced Surgical Care"
                subtitle="With Super specialist doctors and state-of-the-art technology, we cover the complete spectrum of medical specialties"
                headingClassName="text-primary-foreground"
                subtitleClassName="text-primary-foreground/80"
                containerClassName="mb-2 pb-2"
            />
            <DashboardCarousel
                items={ads}
                contentClassName="-ml-3 sm:-ml-4 md:-ml-6 py-3 sm:py-4 px-1"
                basisClassName="pl-3 sm:pl-4 md:pl-6 basis-full sm:basis-1/2 lg:basis-1/3"
                dotClassName="bg-white/35 opacity-100 hover:bg-white/55 h-1 sm:h-1.5"
                activeDotClassName="bg-white opacity-100 h-1 sm:h-1.5  z-10"
                renderItem={(ad) => <AdvertisementCard key={ad.id} ad={ad} />}
            />
        </FullWidthDashboardSection>
    );
}
