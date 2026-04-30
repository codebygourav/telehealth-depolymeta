"use client";

import { DashboardSection } from "@/components/pages/Dashboard/DashboardSection";
import { DoctorCard } from "@/components/pages/Dashboard/DoctorCard";
import { SectionHeader } from "@/components/custom/SectionHeader";
import type { DashboardAvailableDoctor } from "@/types/dashboard-doctors";

interface AvailableDoctorsProps {
  doctors: DashboardAvailableDoctor[];
  onBookNow: (doctorId: string) => void;
  onShowAll: () => void;
}

export function AvailableDoctors({
  doctors,
  onBookNow,
  onShowAll,
  
}: AvailableDoctorsProps) {
  if (!doctors || doctors.length === 0) {
    return null;
  }

  return (
    <DashboardSection>
        <SectionHeader
          title="Available Doctors"
          showAction={true}
          actionText="Show All Doctors"
          subtitle="With Super specialist doctors and state-of-the-art technology, we cover the complete spectrum of medical specialties"
          onActionClick={onShowAll}
        />

      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {doctors.map((doc) => (
          <DoctorCard key={doc.id} doctor={doc} onBookNow={onBookNow} />
        ))}
      </div>

    </DashboardSection>
  );
}
