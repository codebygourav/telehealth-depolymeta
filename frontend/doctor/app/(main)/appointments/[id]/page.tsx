"use client";
import { DietPlanManagement } from "@/app/(main)/appointments/detail-component/DietPlanManagement";
import { VaccinationManagement } from "@/app/(main)/appointments/detail-component/VaccinationManagement";
import CustomTabs, { TabItem } from "@/components/custom/CustomTabs";
import HeroSection from "@/components/ui/hero-section";
import { Skeleton } from "@/components/ui/skeleton";
import { useAppointmentById } from "@/queries/useAppointmentId";
import { useParams, useSearchParams } from "next/navigation";
import { useState } from "react";
import AppointmentHeader from "../detail-component/AppointmentHeader";
import OverviewTab from "../detail-component/OverviewTab";
import PrescriptionTab from "../detail-component/PrescriptionTab";
import PreviousTab from "../detail-component/PreviousTab";
import ReportsTab from "../detail-component/ReportsTab";
import ReviewTab from "../detail-component/ReviewTab";

export default function AppointmentDetail() {
    const params = useParams();
    const id = params?.id as string;
    const searchParams = useSearchParams();
    const initialTab = searchParams.get("tab") || "overview";
    const { data, isLoading, error } = useAppointmentById(id);
    const [activeTab, setActiveTab] = useState(initialTab);

    // Loading skeleton
    if (isLoading) {
        return (
            <div className="space-y-4 sm:space-y-6 px-3 sm:px-4 md:px-6 py-4 sm:py-6">
                <div className="flex flex-col gap-2">
                    <Skeleton className="h-8 w-48 sm:h-9 sm:w-56" />
                    <Skeleton className="h-32 w-full rounded-xl" />
                    <Skeleton className="h-12 w-full rounded-lg" />
                    <Skeleton className="h-64 w-full rounded-xl" />
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="flex items-center justify-center min-h-[400px] px-4">
                <div className="text-center">
                    <div className="text-red-500 text-base sm:text-lg font-semibold mb-2">
                        Error Loading Appointment
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Failed to load appointment details. Please try again.
                    </p>
                </div>
            </div>
        );
    }

    const appointment = data?.data;

    const patient = appointment?.patient as
        | { id?: string; patient_id?: string; user_id?: string }
        | undefined;
    const appointmentPatientId = patient?.id || patient?.patient_id || patient?.user_id;

    const tabs: TabItem[] = [
        {
            key: "overview",
            label: "Overview",
            content: <OverviewTab appointment={appointment} />,
        },
        {
            key: "reports",
            label: "Medical Reports",
            content: <ReportsTab appointment={appointment} />,
        },
        {
            key: "previous",
            label: "Previous Appointments",
            content: <PreviousTab appointment={appointment} />,
        },
        {
            key: "vaccination",
            label: "Vaccination",
            content: <VaccinationManagement patientId={appointmentPatientId} />,
        },
        {
            key: "diet",
            label: "Diet Plan",
            content: <DietPlanManagement patientId={appointmentPatientId} />,
        },
        {
            key: "prescription",
            label: "Prescription",
            content: <PrescriptionTab appointmentId={appointment?.appointment_id} />,
        },
        {
            key: "review",
            label: "Review",
            content: <ReviewTab appointment={appointment} />,
        },
    ];


    // Loading skeleton
    if (isLoading) {

        return (
            <div className="space-y-4 sm:space-y-6 px-3 sm:px-4 md:px-6 py-4 sm:py-6">
                <div className="flex flex-col gap-2">
                    <Skeleton className="h-8 w-48 sm:h-9 sm:w-56" />
                    <Skeleton className="h-32 w-full rounded-xl" />
                    <Skeleton className="h-12 w-full rounded-lg" />
                    <Skeleton className="h-64 w-full rounded-xl" />
                </div>
            </div>
        );
    }



    return (
        <div className="container-max-width w-full mx-auto">
            {/* Page Title */}
            <HeroSection
                title="Appointment Detail"
                description="View and manage appointment details, reports, and prescriptions"
            />

            {/* Appointment Header */}
            <AppointmentHeader appointment={appointment} />

            {/* Custom Tabs with Horizontal Scroll on Mobile */}
            <div className="w-full mt-5">
                <CustomTabs
                    tabs={tabs}
                    activeTab={activeTab}
                    onTabChange={setActiveTab}
                    tabsListClassName="w-full overflow-x-auto overflow-y-hidden  flex-nowrap justify-start sm:justify-start md:justify-start lg:justify-start [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
                />
            </div>
        </div>
    );
}
