"use client";

import { CustomAvatar } from "@/components/custom/custom-avatar";
import { DashboardSection } from "@/components/pages/Dashboard/DashboardSection";
import { SectionHeader } from "@/components/custom/SectionHeader";
import { DashboardCarousel } from "@/components/pages/Dashboard/dashboard-carousel";
import { Card, CardContent, CardFooter } from "@/components/ui";
import { Star } from "lucide-react";
import { useRouter } from "next/navigation";

import { useState } from "react";

export interface Testimonial {
    id: string | number;
    name: string;
    age?: number;
    location: string;
    rating?: number;
    subject: string;
    feedback: string;
    reviewCount: number;
    doctorName: string;
    doctorImage: string;
    patientImage?: string;
    time: string;
}

interface TestimonialsCarouselProps {
    testimonials: Testimonial[];
}

// Read More Component
const ReadMoreText = ({ text, maxLength = 80 }: { text: string; maxLength?: number }) => {
    const [isExpanded, setIsExpanded] = useState(false);

    // Check if text needs truncation
    const needsTruncation = text.length > maxLength;

    // Display text based on expanded state
    const displayText = isExpanded ? text : text.slice(0, maxLength);

    return (
        <div>
            <p className="text-xs leading-relaxed sm:text-sm text-on-surface-variant">
                {displayText}
                {!isExpanded && needsTruncation && "..."}
                {!isExpanded && needsTruncation && (
                    <button
                        onClick={() => setIsExpanded(true)}
                        className="inline ml-1 text-xs font-semibold transition-colors text-emerald-600 sm:text-sm hover:text-emerald-700 hover:underline focus:outline-none"
                    >
                        Read more
                    </button>
                )}
                {isExpanded && (
                    <button
                        onClick={() => setIsExpanded(false)}
                        className="inline ml-1 text-xs font-semibold transition-colors text-emerald-600 sm:text-sm hover:text-emerald-700 hover:underline focus:outline-none"
                    >
                        Read less
                    </button>
                )}
            </p>
        </div>
    );
};

export function TestimonialsCarousel({
    testimonials,
}: TestimonialsCarouselProps) {

    const router = useRouter();

    if (!testimonials || testimonials.length === 0) return null;

    return (
        <DashboardSection className="w-full max-w-full py-6 overflow-hidden sm:py-8">
            <SectionHeader
                title="Here's what our satisfied customers are saying..."
                subtitle="With Super specialist doctors and state-of-the-art technology, we cover the complete spectrum of medical specialties"
                showAction={true}
            />

            <DashboardCarousel
                items={testimonials}
                basisClassName="basis-[100%] sm:basis-[70%] md:basis-1/2 lg:basis-[400px]"
                contentClassName="-ml-3 sm:-ml-4 py-3 sm:py-4 px-1"
                dotColorClassName="bg-gray-300"
                activeDotColorClassName="bg-primary"
                dotBaseClassName="h-1 sm:h-1.5"
                renderItem={(t) => (
                    <Card className="flex flex-col gap-0 p-0 border bg-surface-lowest sm:p- md:p-0 global-radius-10 border-outline-variant/10 shadow-card-lg">
                        <CardContent className="flex flex-col justify-between gap-5 p-4 sm:flex-row sm:items-start ">
                            <div className="flex gap-3 sm:gap-4">
                                <CustomAvatar
                                    src={t.patientImage}
                                    name={t.name}
                                    radius="full"
                                    size="default"
                                    className="rounded-full bg-light-gray size-14"
                                />
                                <div>
                                    <h5 className="text-base font-bold text-on-surface font-headline sm:text-lg">
                                        {t.name}
                                    </h5>
                                    <p className="text-[10px] sm:text-xs text-on-surface-variant font-medium">
                                        {t.age ? `${t.age} Years, ` : ""}
                                        {t.location}
                                    </p>
                                </div>
                            </div>
                            <span className="font-bold span-12 sm:text-xs text-on-surface-variant">
                                {t.time}
                            </span>
                        </CardContent>
                        <CardContent className="mb-6 sm:mb-6 grow ">
                            <div className="flex gap-0.5 mb-1.5 sm:mb-2">
                                {[...Array(5)].map((_, i) => (
                                    <Star
                                        key={i}
                                        className={`w-3 h-3 sm:w-4 sm:h-4 ${i < (t.rating || 5)
                                            ? "text-yellow-400 fill-current"
                                            : "text-yellow-400/80"
                                            }`}
                                    />
                                ))}
                            </div>
                            <h5 className="font-bold italic text-on-surface mb-1.5 sm:mb-2 text-sm sm:text-base">
                                &quot;{t.subject}&quot;
                            </h5>
                            <ReadMoreText text={t.feedback} maxLength={70} />
                        </CardContent>
                        <CardFooter className="flex items-center justify-between gap-3 py-1.5 mt-auto bg-light-gray border-t border-outline-variant/10 sm:items-center sm:gap-4">
                            <p className="text-[10px] sm:text-xs font-bold text-on-surface-variant">
                                {(t.reviewCount > 5 ? "5+" : t.reviewCount) +
                                    " Reviews for " +
                                    t.doctorName}
                            </p>
                            <div
                                onClick={() => router.push("/reviews")}
                                className="transition-opacity cursor-pointer hover:opacity-80"
                            >
                                <CustomAvatar
                                    radius="full"
                                    size="sm"
                                    src={t.doctorImage}
                                    className="w-4 h-4 sm:w-6 sm:h-6 md:w-8 md:h-8"
                                />
                            </div>
                        </CardFooter>
                    </Card>

                )}
            />
        </DashboardSection>
    );
}
