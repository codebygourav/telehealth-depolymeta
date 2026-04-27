"use client";

import React from "react";
import { useAuth } from "@/context/userContext";
import CustomTabs, { TabItem } from "@/components/custom/CustomTabs";
import PersonalInfoForm from "@/components/pages/profile/personal-info";
import ManageAddressForm from "@/components/pages/profile/manage-address";


export default function ProfileTabs() {
    const { user } = useAuth();
    

    if (!user) return <p>Loading...</p>;

    const tabs: TabItem[] = [
        {
            key: "personal_info",
            label: "Personal Info",
            content: <PersonalInfoForm user={user} />,
        },
        {
            key: "manage_address",
            label: "Manage Address",
            content: <ManageAddressForm user={user} />,
        },
    ];

    return (
        <div className="space-y-6 max-w-5xl mx-auto">
            <div className="space-y-1 sm:space-y-2">
                <h1 className="text-primary text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight">
                    Account Settings
                </h1>
                <p className="text-gray-600 dark:text-gray-400 text-xs sm:text-sm md:text-base mt-1 sm:mt-2">
                    Manage your medical profile and personal preferences.
                </p>
            </div>
            
            <CustomTabs
                variant="pill"
                activeTabBg="#013220"
                activeTabColor="white"
                tabs={tabs}
                defaultTab="personal_info"
                tabsListClassName="max-w-md"
            />
        </div>
    );
}