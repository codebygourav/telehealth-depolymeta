"use client";

import * as React from "react";

import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

export type StatusBadgeStatus =
  | "active"
  | "archived"
  | "shared"
  | "uploaded"
  | "pending"
  | "failed"
  | "rejected"
  | "conclusion_report"
  | string;

const getStatusClasses = (status: StatusBadgeStatus) => {
  const s = status?.toString().toLowerCase();
  switch (s) {
    case "active":
      return "bg-primary/10 text-primary border-primary/10";
    case "archived":
      return "bg-muted/10 text-muted border-muted/10";
    case "shared":
      // Tailwind arbitrary values cannot contain spaces.
      return "g-success-badge";
    case "uploaded":
    case "success":
      return "bg-yellow-500/10 text-yellow-500 border-yellow-500/10";
    case "pending":
      return "bg-yellow-500/10 text-yellow-500 border-yellow-500/10";
    case "conclusion_report":
      return "bg-info/10 text-info border-info/10";
    case "failed":
    case "rejected":
    case "error":
      return "bg-destructive/10 text-destructive border-destructive/10";
    default:
      return "bg-light-gray g-text-muted border-light-gray";
  }
};

const defaultLabel = (status: StatusBadgeStatus) => {
  const s = (status ?? "").toString();
  if (!s) return "-";
  // Always display status as lower case, e.g. 'conclusion_report'
  return s.toLowerCase();
};

export interface StatusBadgeProps {
  status: StatusBadgeStatus;
  label?: React.ReactNode;
  className?: string;
}

export function StatusBadge({ status, label, className }: StatusBadgeProps) {
  return (
    <Badge
      variant="outline"
      className={cn(
        "font-bold px-2.5 h-6 rounded-md",
        getStatusClasses(status),
        className
      )}
    >
      {label ?? defaultLabel(status)}
    </Badge>
  );
}
