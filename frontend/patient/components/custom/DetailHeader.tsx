"use client";

import React from "react";
import { useRouter } from "next/navigation";
import { ChevronLeft } from "lucide-react";
import { cn } from "@/lib/utils";

interface DetailHeaderProps {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  className?: string;
}

export const DetailHeader = ({
  title,
  subtitle,
  onBack,
  className
}: DetailHeaderProps) => {
  const router = useRouter();

  const handleBack = () => {
    if (onBack) {
      onBack();
    } else {
      router.back();
    }
  };

  return (
    <header className={cn("flex items-center md:gap-4 gap-1 mb-8", className)}>
      <button
        onClick={handleBack}
        className="p-3 hover:bg-surface-container rounded-2xl transition-all text-primary"
      >
        <ChevronLeft className="w-6 h-6" />
      </button>
      <div>
        <h1 className="md:text-3xl text-1xl font-extrabold tracking-tight text-primary font-headline">
          {title}
        </h1>
        {subtitle && (
          <p className="text-on-surface-variant text-sm md:text-base font-medium">
            {subtitle}
          </p>
        )}
      </div>
    </header>
  );
};
