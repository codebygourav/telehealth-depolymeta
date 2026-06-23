"use client";

import { useState } from "react";
import { useAuth } from "@/context/userContext";
import PersonalInfoForm from "@/components/pages/profile/personal-info";
import ManageAddressForm from "@/components/pages/profile/manage-address";
import { User, MapPin, Bell, Syringe, Utensils } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import HeroSection from "@/components/hero-section";
import WeeklyMealChart from "@/components/pages/profile/weekly-meal-chart";
import MyVaccination from "@/components/pages/profile/my-vaccination";


type TabKey = "personal_info" | "manage_address" | "notifications" | "my_diet" | "my_vaccinations";

export default function ProfilePage() {

    const { user } = useAuth();
    const [activeTab, setActiveTab] = useState<TabKey>("personal_info");

    if (!user) return <p className="p-8 text-center">Loading...</p>;

    const sidebarItems = [
        {
            key: "personal_info" as TabKey,
            label: "Profile Settings",
            icon: User,
        },
        {
            key: "manage_address" as TabKey,
            label: "Manage Address",
            icon: MapPin,
        },
        {
            key: "notifications" as TabKey,
            label: "Notifications",
            icon: Bell,
        },
        {
            key: "my_diet" as TabKey,
            label: "My Diet",
            icon: Utensils,
        },
        {
            key: "my_vaccinations" as TabKey,
            label: "My Vaccinations",
            icon: Syringe,
        },
    ];

    return (
        <div>

            <HeroSection
                title="Profile Settings"
                description="Manage your personal information, address, and notification preferences."
            />

            <div className="flex flex-col md:flex-row gap-8 container-max-width w-full mx-auto">

                {/* Sidebar */}
                <aside className="w-full md:w-64 space-y-2">
                    <div className="bg-white dark:bg-slate-900 global-radius border border-slate-200 dark:border-slate-800 p-4 shadow-sm h-full">
                        {sidebarItems.map((item) => {
                            const Icon = item.icon;
                            const isActive = activeTab === item.key;
                            return (
                                <Button
                                    key={item.key}
                                    variant="outline"
                                    onClick={() => setActiveTab(item.key)}
                                    className={cn(
                                        "w-full flex items-center justify-start gap-3 px-4 py-3 btn-primary-cta-outline text-start",
                                        isActive
                                            ? "text-primary"
                                            : "g-text-dark border border-transparent"
                                    )}
                                >
                                    <Icon className={cn("w-5 h-5", isActive ? "text-primary" : "text-on-surface-variant")} />
                                    {item.label}
                                </Button>
                            );
                        })}
                    </div>
                </aside>

                {/* Content Area */}
                <main className="flex-1 bg-white dark:bg-slate-900 global-radius border border-slate-200 dark:border-slate-800 p-4 md:p-8 shadow-sm min-h-[600px]">
                    {activeTab === "personal_info" && (
                        <div>
                            <h2 className="text-xl font-bold text-slate-900 dark:text-white mb-8">Basic Information</h2>
                            <PersonalInfoForm user={user} />
                        </div>
                    )}
                    {activeTab === "manage_address" && (
                        <div>
                            <h2 className="text-xl font-bold text-slate-900 dark:text-white mb-8">Manage Address</h2>
                            <ManageAddressForm user={user} />
                        </div>
                    )}
                    {activeTab === "notifications" && (
                        <div>
                            <h2 className="text-xl font-bold text-slate-900 dark:text-white mb-8">Notifications</h2>
                            <div className="text-slate-500 dark:text-slate-400 py-10 text-center">
                                Notification settings coming soon.
                            </div>
                        </div>
                    )}
                    {activeTab === "my_diet" && (
                        <div className="w-full">
                            <WeeklyMealChart />
                        </div>
                    )}
                    {activeTab === "my_vaccinations" && (
                        <div className="w-full">
                            <MyVaccination />
                        </div>
                    )}
                </main>
            </div>
        </div>
    );
}