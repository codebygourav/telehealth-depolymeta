"use client";

import { DashboardSection } from "@/components/pages/Dashboard/DashboardSection";
import DoctorCard from "@/components/DoctorCard";
import { SectionHeader } from "@/components/custom/SectionHeader";
import type { DashboardAvailableDoctor } from "@/types/dashboard-doctors";

interface AvailableDoctorsProps {
    doctors: DashboardAvailableDoctor[];
    onShowAll: () => void;
}

export function AvailableDoctors({
    doctors,
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
                {doctors.map((doc) => {
                    const mappedDoctor = {
                        ...doc,
                        years_experience: String(doc.years_experience),
                        consultation_type_label: [doc.consultation_type],
                    } as unknown as any;

                    return (
                        <DoctorCard
                            key={doc.id}
                            doctor={mappedDoctor}
                        />
                    );
                })}
            </div>

        </DashboardSection>
    );
}
