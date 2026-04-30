'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { MedicalReport } from '@/types/medical-reports';
import { ColumnDef } from '@tanstack/react-table';
import { ExternalLink, FileText } from 'lucide-react';
import { useRouter } from 'next/navigation';

export type { MedicalReport };

const getStatusStyle = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'shared':
            return 'bg-success text-white border-success';
        case 'pending':
            return 'bg-warning text-white border-warning';
        default:
            return 'bg-light-gray g-text-muted border-light-gray';
    }
};

function ActionCell({ row }: { row: { original: MedicalReport } }) {
    const router = useRouter();
    const record = row.original;
    const appointmentId = record.doctor?.appoinment_id;
    const timeStatus = record.doctor?.appointment_time_status;

    return (
        <div className="flex flex-wrap items-center gap-2">
            <a
                href={record.file_url}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-2 bg-primary text-white px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-primary/90 transition-all whitespace-nowrap"
            >
                View Report
                <ExternalLink className="w-3.5 h-3.5" />
            </a>
            {appointmentId && (
                <Button
                    variant="outline"
                    size="sm"
                    className="gap-1 p-5 text-sm font-semibold rounded-lg g-border-light hover:border-primary hover:text-primary whitespace-nowrap"
                    onClick={() => {
                        const route = timeStatus === 'upcoming'
                            ? `/appointments/manage-appointment/${appointmentId}`
                            : `/appointments/appoitment-detail/${appointmentId}`;
                        router.push(route);
                    }}
                >
                    Appointment
                    <ExternalLink className="w-3.5 h-3.5" />
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
            <div className="flex items-center gap-4">
                <div className="flex items-center justify-center w-10 h-10 text-gray-600 bg-gray-100 rounded-lg shrink-0">
                    <FileText className="w-5 h-5" />
                </div>
                <span className="text-base font-bold text-primary">
                    {row.original.report_name ?? '-'}
                </span>
            </div>
        ),
    },
    {
        accessorKey: 'type_label',
        header: 'Type',
        cell: ({ row }) => (
            <span className="font-medium text-gray-500">
                {row.original.type_label ?? '-'}
            </span>
        ),
    },
    {
        accessorKey: 'report_date_formatted',
        header: 'Date',
        cell: ({ row }) => (
            <span className="font-medium text-gray-500">
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
                    <Badge
                        variant="outline"
                        className={`capitalize font-bold px-2.5 h-6 ${getStatusStyle(status)}`}
                    >
                        {isShared ? 'Shared' : (status ?? '-')}
                    </Badge>
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
        cell: ({ row }) => <ActionCell row={row} />,
    },
];
