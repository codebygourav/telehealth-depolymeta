"use client";

import { Pill } from "lucide-react";
import { MedicineCard } from "@/components/MedicineCard";
import { Skeleton } from "@/components/ui/skeleton";
import CustomTabs from "@/components/custom/CustomTabs";
import { usePrescriptions } from "@/queries/usePrescriptions";
import { useAuth } from "@/context/userContext";
import { useState } from "react";
import HeroSection from "@/components/hero-section";
import { EmptyState } from "@/components/custom/EmptyState";

interface MedicineListViewProps {
    onViewDetail: (id: string) => void;
}

export const MedicineListView = ({ onViewDetail }: MedicineListViewProps) => {
    const [activeTab, setActiveTab] = useState<"current" | "past">("current");
    const { user } = useAuth();
    const patientID = user?.patient_id;

    const {
        data: prescriptionsResponse,
        isLoading: isListLoading,
        isError: isListError,
    } = usePrescriptions({ patientID, filter: activeTab });

    const prescriptions = prescriptionsResponse?.data || [];

    return (
        <div className="space-y-8 duration-500 animate-in fade-in">

            <HeroSection
                title="Medicines"
                description="Track your current and past medications."
            />

            <CustomTabs
                variant="pill"
                activeTabBg="#013220"
                activeTabColor="white"
                tabs={[
                    { key: "current", label: "Current Medicine" },
                    { key: "past", label: "Past Medicine" },
                ]}
                activeTab={activeTab}
                onTabChange={(val) => setActiveTab(val as "current" | "past")}
                tabsListClassName="max-w-md"
            />

            {isListLoading ? (
                <div className="grid grid-cols-1 gap-6 container-max-width mx-auto w-full">
                    {[1, 2, 3, 4].map((i) => (
                        <Skeleton key={i} className="h-[200px] w-full global-radius" />
                    ))}
                </div>
            ) : isListError ? (
                <div className="container-max-width mx-auto w-fullpy-20 text-center border border-dashed bg-destructive/5 global-radius border-destructive/20">
                    <h3 className="mb-2 text-xl font-bold text-destructive">
                        Failed to load medicines
                    </h3>
                    <p className="text-on-surface-variant">
                        There was an error fetching your medications. Please try again later.
                    </p>
                </div>
            ) : prescriptions.length > 0 ? (
                <div className="grid grid-cols-1 gap-6 container-max-width mx-auto w-full">
                    {prescriptions.map((prescription) => (
                        <MedicineCard
                            key={prescription.appointment_id}
                            prescription={prescription}
                            onViewDetail={(id) => onViewDetail(id)}
                        />
                    ))}
                </div>
            ) : (
                <EmptyState
                    icon={<Pill />}
                    title="No medicines found"
                    description={`You don't have any ${activeTab} medications at the moment.`}
                />
            )}
        </div>
    );
};
