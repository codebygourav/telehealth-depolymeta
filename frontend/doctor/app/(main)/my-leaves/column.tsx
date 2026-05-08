"use client";

import type { ColumnDef } from "@tanstack/react-table";
import type { LeaveItem } from "@/types/leave";
import { Badge } from "@/components/ui/badge";
import { getStatusColor } from "@/src/utils/getStatusColor";

export const leaveColumns: ColumnDef<LeaveItem>[] = [
    {
        accessorKey: "date",
        header: "Date",
        cell: ({ row }) => {
            return (
                <div className="flex gap-1 items-center text-nowrap">
                    <span className="font-medium text-muted-foreground">{row.original.start_date_formatted}</span>
                    <p className="text-muted-foreground">-</p>
                    <span className="font-medium text-muted-foreground">{row.original.end_date_formatted}</span>
                </div>
            )
        },
    },
    {
        accessorKey: "type_label",
        header: "Leave Type",
        cell: ({ row }) => {
            return <span className="font-medium text-muted-foreground">{row.original.type_label || "-"}</span>;
        },
    },
    {
        accessorKey: "duration_text",
        header: "Day",
        cell: ({ row }) => <span className="font-medium text-muted-foreground whitespace-nowrap">{row.original.duration_text || "-"}</span>,
    },
    {
        accessorKey: "applied_date_formatted",
        header: "Applied On",
        cell: ({ row }) => <span className="font-medium text-muted-foreground whitespace-nowrap">{row.original.applied_date_formatted || "-"}</span>,
    },
    {
        accessorKey: "status",
        header: "Status",
        cell: ({ row }) => {
            const item = row.original;
            return (
                <Badge
                    className={`${getStatusColor(
                        "appointment",
                        item.status
                    )} gap-1`}
                >
                    {item.status_label || "Completed"}
                </Badge>
            );
        },
    },
];