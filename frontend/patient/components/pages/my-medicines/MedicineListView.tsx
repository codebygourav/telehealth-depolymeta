"use client";

import { Pill } from "lucide-react";
import { MedicineCard } from "@/components/pages/my-medicines/MedicineCard";
import { Skeleton } from "@/components/ui/skeleton";
import CustomTabs from "@/components/custom/CustomTabs";
import { usePrescriptions } from "@/queries/usePrescriptions";
import { useAuth } from "@/context/userContext";
import { useState } from "react";

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

    console.log("prescriptions : ", prescriptions);

    return (
        <div className="space-y-8 animate-in fade-in duration-500">
            <header className="space-y-1.5 sm:space-y-2 md:space-y-3">
                <h1 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold tracking-tight text-primary font-headline">
                    Medicines
                </h1>
                <p className="text-sm sm:text-base md:text-lg text-on-surface-variant">
                    Track your current and past medications.
                </p>
            </header>

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
                className="max-w-md"
            />

            {isListLoading ? (
                <div className="grid grid-cols-1 gap-6">
                    {[1, 2, 3, 4].map((i) => (
                        <Skeleton key={i} className="h-[200px] w-full rounded-[2rem]" />
                    ))}
                </div>
            ) : isListError ? (
                <div className="text-center py-20 bg-destructive/5 rounded-[2rem] border border-dashed border-destructive/20">
                    <h3 className="text-xl font-bold text-destructive mb-2">
                        Failed to load medicines
                    </h3>
                    <p className="text-on-surface-variant">
                        There was an error fetching your medications. Please try again later.
                    </p>
                </div>
            ) : prescriptions.length > 0 ? (
                <div className="grid grid-cols-1 gap-6">
                    {prescriptions.map((prescription) => (
                        <MedicineCard
                            key={prescription.appointment_id}
                            prescription={prescription}
                            status={activeTab}
                            onViewDetail={(id) => onViewDetail(id)}
                        />
                    ))}
                </div>
            ) : (
                <div className="text-center py-20 bg-surface-container-low rounded-[2rem] border border-dashed border-outline-variant/20">
                    <div className="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 text-on-surface-variant/30">
                        <Pill className="w-8 h-8" />
                    </div>
                    <h3 className="text-xl font-bold text-primary mb-2">
                        No medicines found
                    </h3>
                    <p className="text-on-surface-variant">
                        You don't have any {activeTab} medications at the moment.
                    </p>
                </div>
            )}
        </div>
    );
};
