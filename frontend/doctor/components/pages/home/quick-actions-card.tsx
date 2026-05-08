"use client";

import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";

export interface QuickActionItem {
    id: number | string;
    title: string;
    icon: React.ReactNode;
    href: string;
}

interface QuickActionsCardProps {
    title?: string;
    actions: QuickActionItem[];
}

export default function QuickActionsCard({
    title = "Quick Actions",
    actions,
}: QuickActionsCardProps) {
    const router = useRouter();
    return (
        <div className="border-light-gray p-5 rounded-lg shadow-[0px_2px_4px_0px_#0000001A]">

            <h3 className="text-[#1F1E1E] font-bold text-lg">{title}</h3>

            <div className="mt-5">
                <div className="grid gap-3 grid-cols-2 md:grid-cols-5">
                    {actions.map((action) => (
                        <Button
                            key={action.id}
                            variant="outline"
                            className="h-auto flex-col gap-2 py-6 cursor-pointer border-light-gray"
                            onClick={() => router.push(action.href)}
                        >
                            {action.icon}
                            <span className="text-[#1F1E1E] font-semibold text-sm">{action.title}</span>
                        </Button>
                    ))}
                </div>
            </div>
        </div>
    );
}