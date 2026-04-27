// components/ui/CustomTabs.tsx
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
}: CustomTabsProps) => {
    const [internalActiveTab, setInternalActiveTab] = React.useState(
        defaultTab || tabs[0]?.key || ""
    );
    const activeTab = controlledActiveTab !== undefined
        ? controlledActiveTab
        : internalActiveTab;
    const handleTabChange = (value: string) => {
        if (controlledActiveTab === undefined) {
            setInternalActiveTab(value);
        }
        onTabChange?.(value);
    };
    return (
        <Tabs value={activeTab} onValueChange={handleTabChange} className={cn("w-full", className)}>
            {/* <TabsList className={cn("grid w-full grid-cols-4", tabsListClassName)}> */}
            <TabsList className={cn("flex justify-center items-center w-full gap-4", tabsListClassName)}>
                {tabs.map((tab) => (
                    <TabsTrigger
                        key={tab.key}
                        value={tab.key}
                        style={{
                            ['--tab-active-bg' as string]: `var(--${color})`,
                            ['--tab-active-text' as string]: `var(--${color}-foreground)`,
                        }}
                        className={cn(
                            "transition-all duration-200 px-6 py-3.5",
                            "data-[state=active]:bg-(--tab-active-bg)",
                            "data-[state=active]:text-(--tab-active-text)",
                            `hover:bg-${color}-50`,
                            tabsTriggerClassName
                        )}
                    >
                        {tab.label}
                    </TabsTrigger>
                ))}
            </TabsList>
            {tabs.map((tab) => (
                tab.content && (
                    <TabsContent
                        key={tab.key}
                        value={tab.key}
                        className={"mt-4 " + tabsContentClassName}
                    >
                        {tab.content}
                    </TabsContent>
                )
            ))}
        </Tabs>
    );
};
export default CustomTabs;