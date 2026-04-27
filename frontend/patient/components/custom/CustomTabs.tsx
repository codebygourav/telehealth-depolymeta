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
    rightSlot?: React.ReactNode; // ✅ new prop
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
    color = "primary",
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
            className={cn("w-full", className)}
        >
            {/* Top Row */}
            <div className="flex w-full items-center justify-between gap-4">
                <TabsList
                    className={cn(
                        "flex items-center transition-all duration-300",
                        rightSlot ? "w-auto justify-start" : "w-full justify-center",
                        isPill
                            ? "bg-surface-container-low py-7 rounded-2xl gap-1 px-2"
                            : "bg-transparent gap-4",
                        tabsListClassName
                    )}
                >
                    {tabs.map((tab) => (
                        <TabsTrigger
                            key={tab.key}
                            value={tab.key}
                            style={
                                {
                                    ["--tab-active-bg" as string]:
                                        activeTabBg || (isPill ? "white" : `var(--${color})`),
                                    ["--tab-active-text" as string]:
                                        activeTabColor ||
                                        (isPill
                                            ? "var(--primary)"
                                            : `var(--${color}-foreground)`),
                                } as React.CSSProperties
                            }
                            className={cn(
                                "transition-all duration-300 font-source-sans font-bold",
                                isPill
                                    ? cn(
                                        "px-6 py-5 rounded-[1.5rem] flex-1 text-[#333333]",
                                        "data-[state=active]:bg-(--tab-active-bg) data-[state=active]:text-(--tab-active-text) data-[state=active]:shadow-sm"
                                    )
                                    : cn(
                                        "px-6 py-3.5 rounded-xl",
                                        "data-[state=active]:bg-(--tab-active-bg) data-[state=active]:text-(--tab-active-text)",
                                        `hover:bg-${color}-50`
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
                            className={cn("mt-6 focus-visible:outline-none", tabsContentClassName)}
                        >
                            {tab.content}
                        </TabsContent>
                    )
            )}
        </Tabs>
    );
};

export default CustomTabs;