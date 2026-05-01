'use client';

import { Button } from '@/components/ui/button';
import { StatusBadge, StatusBadgeStatus } from "@/components/custom/StatusBadge";
import { MedicalReport } from '@/types/medical-reports';
import { ColumnDef } from '@tanstack/react-table';
import { ChevronRight, FileText } from 'lucide-react';
import { useRouter } from 'next/navigation';

export type { MedicalReport };

type ColumnMeta = {
    headerClassName?: string;
    cellClassName?: string;
};

function ActionCell({ row }: { row: { original: MedicalReport } }) {
    const router = useRouter();
    const record = row.original;
    const appointmentId = record.doctor?.appoinment_id;
    const timeStatus = record.doctor?.appointment_time_status;
    return (
        <div className="flex flex-nowrap items-center gap-2">
            <Button
                type="button"
                className="inline-flex items-center gap-2 h-9 btn-primary-cta global-radius span-12"
                onClick={() => {
                    window.open(record.file_url, '_blank', 'noopener,noreferrer');
                }}
            >
                View Report
                <ChevronRight className="w-3.5 h-3.5 m-0" />
            </Button>
       
            {appointmentId && (
                <Button
                    variant="outline"
                    className="inline-flex items-center gap-2 h-9 btn-primary-cta g-text-dark global-radius span-12"
                    onClick={() => {
                        const route = timeStatus === 'upcoming'
                            ? `/appointments/manage-appointment/${appointmentId}`
                            : `/appointments/appoitment-detail/${appointmentId}`;
                        router.push(route);
                    }}
                >
                    Appointment
                    <ChevronRight className="w-3.5 h-3.5 m-0" />
                </Button>
            )}
        </div>
    );
}

export const medicalRecordsColumns: ColumnDef<MedicalReport>[] = [
    {
        accessorKey: 'report_name',
        header: 'Record Name',
        cell: ({ row }) => (
            <div className="flex items-center gap-4 min-w-0">
                <div className="flex items-center justify-center w-10 h-10 text-gray-600 bg-gray-100 rounded-lg shrink-0">
                    <FileText className="w-5 h-5" />
                </div>
                <span className="text-base font-bold g-text-muted truncate">
                    {row.original.report_name ?? '-'}
                </span>
            </div>
        ),
    },
    {
        accessorKey: 'type_label',
        header: 'Type',
        cell: ({ row }) => (
            <span className="font-medium g-text-muted">
                {row.original.type_label ?? '-'}
            </span>
        ),
    },
    {
        accessorKey: 'report_date_formatted',
        header: 'Date',
        cell: ({ row }) => (
            <span className="font-medium g-text-muted">
                {row.original.report_date_formatted ?? '-'}
            </span>
        ),
    },

    {
        
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.original.status;
            const isShared = status === 'shared';
            return (
                <div className="flex flex-col items-start gap-1">
                    <StatusBadge status={status.toLowerCase() as StatusBadgeStatus} label={isShared ? "Shared" : undefined} />
                    {isShared && (
                        <span className="text-[#475467] font-medium text-[13px] leading-tight mt-0.5 whitespace-nowrap">
                            with {row.original.doctor?.name ?? '-'}
                        </span>
                    )}
                </div>
            );
        },
    },
    {
        id: 'action',
        header: 'Action',
        meta: {
            headerClassName: "flex-wrap items-center justify-center",
            cellClassName: "flex-wrap items-center justify-center",
        } satisfies ColumnMeta,
        cell: ({ row }) => <ActionCell row={row} />,
    },
];
