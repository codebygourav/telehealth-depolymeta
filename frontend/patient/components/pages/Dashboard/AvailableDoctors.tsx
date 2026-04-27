"use client";

import { CustomAvatar } from "@/components/custom/custom-avatar";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import type { AvailableDoctor } from "@/types/home";
import { ArrowRight, Star } from "lucide-react";
import { DashboardCarousel } from "@/components/pages/Dashboard/dashboard-carousel";
import { useRouter } from "next/navigation";
import { SectionHeader } from "@/components/custom/SectionHeader";

interface AvailableDoctorsProps {
  doctors: AvailableDoctor[];
  onBookNow: (doctorId: string) => void;
  onShowAll: () => void;
}

export function AvailableDoctors({
  doctors,
  onBookNow,
  onShowAll,
}: AvailableDoctorsProps) {
  // const useCarousel = Array.isArray(doctors) && doctors.length > 3;
  const router = useRouter();

  if (!doctors || doctors.length === 0) {
    return null;
  }

  // Reusable Doctor Card Component
  const DoctorCardComponent = ({ doc }: { doc: AvailableDoctor }) => (
    <Card className="rounded-[5px] h-full flex flex-col shadow-card-lg">
      <CardContent className="flex flex-col h-full">
        <div className="flex gap-4 sm:gap-5">
          {/* Left Image */}
          <div className="shrink-0">
            <div className="md:w-30 w-20 h-auto rounded-md bg-[#d9d9d9] overflow-hidden">
              <img
                src={doc.avatar}
                alt={doc.name}
                className="w-full h-full object-cover"
              />
            </div>
          </div>

          {/* Right Content */}
          <div className="flex-1 min-w-0">
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0">
                <h3 className="text-base font-semibold text-[#222] leading-tight">
                  {doc.name}
                </h3>
              </div>

              {typeof doc.rating === "number" && doc.rating > 0 && (
                <div className="flex items-center gap-1 rounded-[5px] bg-[#f3f4f6] px-2.5 py-0.5 text-sm font-semibold text-primary shrink-0">
                  <Star className="w-3 h-3 fill-current text-primary" />
                  <span className="text-xs font-semibold">{doc.rating}</span>
                </div>
              )}
            </div>

            <p className="mt-1 sm:text-[13px] font-semibold text-primary">
              {Array.isArray(doc.speciality) && doc.speciality.length > 0
                ? doc.speciality[0]
                : "Cardiologist"}
            </p>

            <p className="mt-2 text-xs font-medium text-[#2e2e2e] flex gap-1 flex-wrap">
              <span>Exp: {doc.years_experience || 14} years</span>
              <span>•</span>
              <span>
                {Array.isArray(doc.languages_known) && doc.languages_known.length > 0
                  ? doc.languages_known.join(", ")
                  : "English, Hindi, Punjabi"}
              </span>
            </p>

            <p className="mt-3 text-xs text-[#2e2e2e]">
              Consultation Type:{" "}
              <span className="font-bold text-primary">
                {doc.consultation_type || "Video"}
              </span>
            </p>

            <p className="mt-2 text-xs font-normal text-[#2e2e2e]">
              Fee: <span className="font-bold text-[#111]">₹{doc.consultation_fee ?? 1}</span>
            </p>
          </div>
        </div>

        <div className="mt-auto pt-3">
          <div className="mb-3 h-px w-full bg-[#e5e7eb]" />

          <Button
            onClick={() => router.push(`/find-doctors/${doc.id}`)}
            className="w-full md:h-8 h-9 rounded-[5px] bg-primary text-white md:text-xs text-sm font-medium"
          >
            Book Your Appointment
          </Button>
        </div>
      </CardContent>
    </Card>
  );

  return (
    <section >
        <SectionHeader
          title="Available Doctors"
          showAction={true}
          onActionClick={onShowAll}
        />

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        {doctors.map((doc) => (
          <DoctorCardComponent key={doc.id} doc={doc} />
        ))}
      </div>

    </section>
  );
}