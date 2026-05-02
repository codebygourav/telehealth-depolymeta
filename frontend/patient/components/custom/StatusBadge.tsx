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
  const commonClasses = "!global-radius p-3";
  const statusClasses = {
    active: "bg-primary/10 text-primary border-primary/10 " + commonClasses,
    archived: "bg-muted/10 text-muted border-muted/10 " + commonClasses,
    shared: "g-success-badge " + commonClasses,
    uploaded: "g-success-badge " + commonClasses,
    success: "g-success-badge " + commonClasses,
    pending: "bg-yellow-400/10 text-yellow-500 border-yellow-500/10 " + commonClasses,
    conclusion_report: "bg-info/10 text-info border-info/10",
    error: "bg-destructive/10 text-destructive border-destructive/10 " + commonClasses,
    default: "bg-light-gray g-text-muted border-light-gray " + commonClasses,
    video: "bg-green-500/10 text-green-500 border-green-500/10 " + commonClasses,
    in_person: "bg-blue-500/10 text-blue-500 border-blue-500/10 " + commonClasses,
    paid: "bg-green-500/10 text-green-500 border-green-500/10 " + commonClasses,
  }
  switch (s) {
    case "active":
      return statusClasses.active;
    case "archived":
      return statusClasses.archived;
    case "shared":
      // Tailwind arbitrary values cannot contain spaces.
      return statusClasses.shared;
    case "uploaded":
    case "success":
      return statusClasses.success;
    case "pending":
      return statusClasses.pending;
    case "conclusion_report":
      return statusClasses.conclusion_report;
    case "video":
      return statusClasses.video;
    case "paid":
      return statusClasses.paid;
    case "failed":
    case "rejected":
    case "error":
      return statusClasses.error;
    default:
      return statusClasses.default;
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
