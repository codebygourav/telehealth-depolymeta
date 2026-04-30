"use client";

import { Button, Card, CardContent, CardDescription, CardTitle } from "@/components/ui";
import { ChevronRight, CalendarCheck2, Video } from "lucide-react";
import Image from "next/image";
import Link from "next/link";

export default function QuickLinks() {
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

    return (
        <div className="grid grid-cols-1 gap-5 md:grid-cols-2 ">
            {cards.map((card) => {
                const Icon = card.icon;

                return (
                    <Link key={card.title} href={card.href} className="flex h-full">
                        <Card
                            className={`w-full rounded-[10px] shadow-card-lg p-0 border-2 custom-card-design`}
                        >
                            <CardContent className="flex h-full flex-col p-0">
                                <div className="bg-light-gray p-5 global-radius">
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
                                <div className="mt-auto flex items-center justify-between gap-4 p-5">
                                    <div className="space-y-1">
                                        <CardTitle className="card-heading-size font-semibold text-foreground">
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
                                        className="h-8 w-8 shrink-0 rounded-full border-foreground/30 bg-transparent"
                                    >
                                    <ChevronRight className="size-5"/>
                                        <span className="sr-only">{card.title}</span>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                );
            })}
        </div>
    );
}
