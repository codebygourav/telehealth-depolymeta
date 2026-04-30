"use client";

import { cn } from "@/lib/utils";
import { DashboardSection } from "@/components/pages/Dashboard/DashboardSection";

interface FullwidthDashboardSectionProps {
  children: React.ReactNode;
  className?: string;
  innerClassName?: string;
  sectionClassName?: string;
}

export function FullWidthDashboardSection({
  children,
  className,
  innerClassName,
  sectionClassName,
}: FullwidthDashboardSectionProps) {
  return (
    <div className={cn("full-bleed", className)}>
      <div className={cn("container-max-width-full mx-auto", innerClassName)}>
        <DashboardSection className={cn("py-6 sm:py-8 container-max-width mx-auto", sectionClassName)}>{children}</DashboardSection>
      </div>
    </div>
  );
}

