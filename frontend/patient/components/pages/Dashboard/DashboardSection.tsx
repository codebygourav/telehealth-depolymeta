"use client";

import { cn } from "@/lib/utils";

interface DashboardSectionProps {
    children: React.ReactNode;
    className?: string;
}

export function DashboardSection({ children, className }: DashboardSectionProps) {
    return <section className={cn("container-max-width mx-auto space-y-4 py-9", className)}>{children}</section>;
}
