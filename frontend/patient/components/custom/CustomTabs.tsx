"use client";

import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { cn } from "@/lib/utils";
import React from "react";

export interface TabItem {
    key: string;
    label: React.ReactNode;
    content?: React.ReactNode;
}

interface CustomTabsProps {
    tabs: TabItem[];
    defaultTab?: string;
    activeTab?: string;
    onTabChange?: (value: string) => void;
    className?: string;
    tabsListClassName?: string;
    tabsTriggerClassName?: string;
    tabsContentClassName?: string;
    color?: string;
    activeTabBg?: string;
    activeTabColor?: string;
    variant?: "default" | "pill";
    rightSlot?: React.ReactNode;
}

const CustomTabs = ({
    tabs,
    defaultTab,
    activeTab: controlledActiveTab,
    onTabChange,
    className,
    tabsListClassName,
    tabsTriggerClassName,
    tabsContentClassName,
    color, // Don't need to allow override, force primary
    activeTabBg,
    activeTabColor,
    variant = "default",
    rightSlot,
}: CustomTabsProps) => {
    const [internalActiveTab, setInternalActiveTab] = React.useState(
        defaultTab || tabs[0]?.key || ""
    );

    const activeTab =
        controlledActiveTab !== undefined ? controlledActiveTab : internalActiveTab;

    const handleTabChange = (value: string) => {
        if (controlledActiveTab === undefined) {
            setInternalActiveTab(value);
        }
        onTabChange?.(value);
    };

    const isPill = variant === "pill";

    return (
        <Tabs
            value={activeTab}
            onValueChange={handleTabChange}
            className={cn("w-full container-max-width mx-auto", className)}
        >
            {/* Top Row */}
            <div className="flex items-center justify-between gap-4">
                <TabsList
                    className={cn(
                        "flex items-center transition-all duration-300 !border border-light-gray",
                        rightSlot ? "w-auto justify-start" : "w-full justify-center",
                        isPill
                            ? "bg-light-gray py-7 global-radius gap-1 px-2"
                            : "bg-primary gap-4",
                        tabsListClassName
                    )}
                >
                    {tabs.map((tab) => (
                        <TabsTrigger
                            key={tab.key}
                            value={tab.key}
                            className={cn(
                                "transition-all duration-300 font-source-sans font-bold g-text-md",
                                // Always apply text-primary for tab text and active tab text
                                isPill
                                    ? cn(
                                        "px-6 py-3 h-auto global-radius flex-1 g-text-dark g-text-md",
                                        "data-[state=active]:bg-primary data-[state=active]:text-white data-[state=active]:shadow-sm"
                                    )
                                    : cn(
                                        "px-6 py-3 h-auto global-radius g-text-dark",
                                        "data-[state=active]:bg-primary data-[state=active]:text-white",
                                        "hover:bg-primary/80"
                                    ),
                                tabsTriggerClassName
                            )}
                        >
                            {tab.label}
                        </TabsTrigger>
                    ))}
                </TabsList>

                {rightSlot && <div className="shrink-0">{rightSlot}</div>}
            </div>

            {tabs.map(
                (tab) =>
                    tab.content && (
                        <TabsContent
                            key={tab.key}
                            value={tab.key}
                            className={cn("focus-visible:outline-none", tabsContentClassName)}
                        >
                            {tab.content}
                        </TabsContent>
                    )
            )}
        </Tabs>
    );
};

export default CustomTabs;