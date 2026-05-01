"use client";

import React, { useState } from "react";
import { useAuth } from "@/context/userContext";
import PersonalInfoForm from "@/components/pages/profile/personal-info";
import ManageAddressForm from "@/components/pages/profile/manage-address";
import { User, MapPin, Bell } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";


type TabKey = "personal_info" | "manage_address" | "notifications";

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
    ];

    return (
        <div className="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
            <div className="flex flex-col md:flex-row gap-8">
                {/* Sidebar */}
                <aside className="w-full md:w-80 space-y-2">
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm">
                        {sidebarItems.map((item) => {
                            const Icon = item.icon;
                            const isActive = activeTab === item.key;
                            return (
                                <Button
                                    key={item.key}
                                    variant="outline"
                                    onClick={() => setActiveTab(item.key)}
                                    className={cn(
                                        "w-full flex items-center gap-3 px-4 py-3 btn-primary-cta-outline",
                                        isActive
                                            ? "bg-primary text-primary-foreground hover:bg-primary/90"
                                            : "text-on-surface-variant hover:bg-light-gray border border-transparent"
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
                <main className="flex-1 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 md:p-8 shadow-sm min-h-[600px]">
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
                </main>
            </div>
        </div>
    );
}