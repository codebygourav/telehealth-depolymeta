"use client";

import { ColumnDef } from "@tanstack/react-table";
import { Badge } from "@/components/ui/badge";
import type { PatientAppointmentRow } from "@/types/patients";
import { useRouter } from "next/navigation";
import { getStatusColor } from "@/src/utils/getStatusColor";
import { Button } from "@/components/ui";
import { ChevronRight } from 'lucide-react';

const getStatusVariant = (status: string) => {
    switch (status?.toLowerCase()) {
        case "completed":
            return "default";
        case "failed":
            return "destructive";
        case "rescheduled":
            return "secondary";
        default:
            return "outline";
    }
};

export const patientsColumns: ColumnDef<PatientAppointmentRow>[] = [

    {
        accessorKey: "patient.name",
        header: "Patient Name",
        cell: ({ row }) => {
            const patient = row.original.patient;
            return (
                <div>
                    <p className="font-medium">{patient?.name ?? "-"}</p>
                    <p className="text-xs text-muted-foreground">
                        {patient?.patient_id ?? "-"}
                    </p>
                </div>
            );
        },
    },
    // {
    //     accessorKey: "patient.email",
    //     header: "Email",
    //     cell: ({ row }) => row.original.patient?.email ?? "-",
    // },
    {
        accessorKey: "patient.phone",
        header: "Phone",
        cell: ({ row }) => row.original.patient?.phone ?? "-",
    },
    {
        accessorKey: "appointment_date_formatted",
        header: "Appointment Date",
        cell: ({ row }) => row.original.appointment_date_formatted ?? "-",
    },
    {
        accessorKey: "appointment_time_formatted",
        header: "Time",
        cell: ({ row }) => {
            const item = row.original;
            return `${item.appointment_time_formatted ?? "-"} - ${item.appointment_end_time_formatted ?? "-"
                }`;
        },
    },
    {
        accessorKey: "consultation_type_label",
        header: "Consultation Type",
        cell: ({ row }) => row.original.consultation_type_label ?? "-",
    },
    {
        accessorKey: "status_label",
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
    {
        accessorKey: "fee_amount",
        header: "Fee",
        cell: ({ row }) => `₹ ${row.original.fee_amount ?? "0"}`,
    },
    {
        accessorKey: "Action",
        header: "Action",
        cell: ({ row }) => {
            const router = useRouter();
            return (
                <Button
                    variant="default"
                    className="px-3.5 rounded-md h-auto py-2 font-semibold"
                    onClick={() => router.push(`/appointments/${row.original.appointment_id}`)}
                >
                    View Details
                    <ChevronRight color="#fff" size={14} strokeWidth={3} />
                </Button>
            );
        },
    },
];
