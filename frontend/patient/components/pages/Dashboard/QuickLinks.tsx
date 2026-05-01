"use client";

import { Button, Card, CardContent, CardDescription, CardTitle } from "@/components/ui";
import { ChevronRight, CalendarCheck2, Video } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { DashboardCarousel } from "@/components/pages/Dashboard/dashboard-carousel";
import { useIsMobile } from "@/hooks/use-mobile";

export default function QuickLinks() {
    const isMobile = useIsMobile();
    const cards = [
        {
            title: "Instant Video Consultation",
            description: "Book a video consultation with a doctor",
            href: "/find-doctors?consultationType=video",
            image: "/assets/images/instant-video.png",
            imageAlt: "Video consultation",
            icon: Video,
        },
        {
            title: "Book In-Clinic Appointment",
            description: "Your health summary is looking.",
            href: "/find-doctors?consultationType=in-person",
            image: "/assets/images/instant-in-person.png",
            imageAlt: "In-clinic appointment",
            icon: CalendarCheck2,
        },
    ];

    const renderCard = (card: (typeof cards)[number]) => {
        return (
            <Link key={card.title} href={card.href} className="flex h-full">
                <Card className="w-full rounded-[10px] shadow-card-lg p-0 border-2 custom-card-design">
                    <CardContent className="flex flex-col h-full p-0">
                        <div className="p-5 bg-light-gray global-radius">
                            <div className="relative aspect-[16/9] w-full overflow-hidden global-radius">
                                <Image
                                    src={card.image}
                                    alt={card.imageAlt}
                                    fill
                                    sizes="(max-width: 768px) 100vw, 50vw"
                                    className="object-cover global-radius"
                                />
                            </div>
                        </div>
                        <div className="flex items-center justify-between gap-4 p-5 mt-auto">
                            <div className="space-y-1">
                                <CardTitle className="font-semibold card-heading-size text-foreground">
                                    {card.title}
                                </CardTitle>
                                <CardDescription className="card-sub-heading-size text-muted-foreground">
                                    {card.description}
                                </CardDescription>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="w-8 h-8 bg-transparent rounded-full shrink-0 border-foreground/90"
                            >
                                <ChevronRight className="size-5" />
                                <span className="sr-only">{card.title}</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </Link>
        );
    };

    if (isMobile) {
        return (
            <DashboardCarousel
                items={cards}
                basisClassName="basis-full md:basis-1/2"
                renderItem={(card) => renderCard(card)}
            />
        );
    }

    return (
        <div className="grid grid-cols-1 gap-5 md:grid-cols-2 ">
            {cards.map((card) => renderCard(card))}
        </div>
    );
}
