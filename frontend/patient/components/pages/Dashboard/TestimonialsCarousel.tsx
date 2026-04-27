"use client";

import { CustomAvatar } from "@/components/custom/custom-avatar";
import { SectionHeader } from "@/components/custom/SectionHeader";
import { DashboardCarousel } from "@/components/pages/Dashboard/dashboard-carousel";
import { Separator } from "@/components/ui";
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
      <p className="text-xs sm:text-sm text-on-surface-variant leading-relaxed">
        {displayText}
        {!isExpanded && needsTruncation && "..."}
        {!isExpanded && needsTruncation && (
          <button
            onClick={() => setIsExpanded(true)}
            className="text-emerald-600 text-xs sm:text-sm font-semibold ml-1 hover:text-emerald-700 hover:underline focus:outline-none transition-colors inline"
          >
            Read more
          </button>
        )}
        {isExpanded && (
          <button
            onClick={() => setIsExpanded(false)}
            className="text-emerald-600 text-xs sm:text-sm font-semibold ml-1 hover:text-emerald-700 hover:underline focus:outline-none transition-colors inline"
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
    <section className="py-6 sm:py-8 w-full max-w-full overflow-hidden">
      <SectionHeader
        title="Here's what our satisfied customers are saying..."
        subtitle="See what your patients are saying about their experiences with you."
      />

      <DashboardCarousel
        items={testimonials}
        basisClassName="basis-[100%] sm:basis-[70%] md:basis-1/2 lg:basis-[400px]"
        contentClassName="-ml-3 sm:-ml-4 py-3 sm:py-4 px-1"
        dotClassName="bg-gray-300 h-1 sm:h-1.5"
        activeDotClassName="bg-primary h-1 sm:h-1.5"
        renderItem={(t) => (
          <div className="h-full bg-surface-lowest p-4 sm:p-5 md:p-6 rounded-[5px] border border-outline-variant/10 shadow-card-lg flex flex-col">
            <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-5 mb-4 sm:mb-6">
              <div className="flex gap-3 sm:gap-4">
                <CustomAvatar
                  src={(t as any).patientImage}
                  radius="full"
                  size="xl"
                  className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16"
                />
                <div>
                  <h4 className="font-bold text-on-surface font-headline text-base sm:text-lg">
                    {t.name}
                  </h4>
                  <p className="text-[10px] sm:text-xs text-on-surface-variant font-medium">
                    {t.age ? `${t.age} Years, ` : ""}
                    {t.location}
                  </p>
                </div>
              </div>
              <span className="text-[10px] sm:text-xs text-on-surface-variant font-bold">
                {t.time}
              </span>
            </div>

            <div className="mb-3 sm:mb-4 grow">
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
                "{t.subject}"
              </h5>
              <ReadMoreText text={t.feedback} maxLength={70} />
            </div>
            <Separator className="bg-gray-200 h-[1px] w-full mb-3" />

            <div className="mt-auto flex items-center sm:items-center justify-between gap-3 sm:gap-4">
              <p className="text-[10px] sm:text-xs font-bold text-on-surface-variant">
                {(t.reviewCount > 5 ? "5+" : t.reviewCount) +
                  " Reviews for " +
                  t.doctorName}
              </p>
              <div
                onClick={() => router.push("/reviews")}
                className="cursor-pointer hover:opacity-80 transition-opacity"
              >
                <CustomAvatar
                  radius="full"
                  size="xl"
                  src={t.doctorImage}
                  className="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12"
                />
              </div>
            </div>
          </div>
        )}
      />
    </section>
  );
}