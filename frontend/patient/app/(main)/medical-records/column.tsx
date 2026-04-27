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
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'pending':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        default:
            return 'bg-gray-100 text-gray-600 border-gray-200';
    }
};

function ActionCell({ row }: { row: { original: MedicalReport } }) {
    const router = useRouter();
    const record = row.original;
    const appointmentId = record.doctor?.appoinment_id;
    const timeStatus = record.doctor?.appointment_time_status;

    return (
        <div className="flex items-center gap-2 flex-wrap">
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
                    className="gap-1 rounded-lg p-5 font-semibold text-sm border-gray-200 hover:border-primary hover:text-primary whitespace-nowrap"
                    onClick={() => {
                        const route = timeStatus === 'upcoming'
                            ? `/appointments/manage-appointment/${appointmentId}`
                            : `/appointments/appoitment-detail/${appointmentId}`;
                        router.push(route);
                    }}
                    // onClick={() => {
                    //     const route = timeStatus === 'past'
                    //         ? `/my-appointments/past-appointments/appoitment-detail/${appointmentId}`
                    //         : `/appointments/appoitment-detail/${appointmentId}`;
                    //     router.push(route);
                    // }}
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
                <div className="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 shrink-0">
                    <FileText className="w-5 h-5" />
                </div>
                <span className="font-bold text-primary text-base">
                    {row.original.report_name ?? '-'}
                </span>
            </div>
        ),
    },
    {
        accessorKey: 'type_label',
        header: 'Type',
        cell: ({ row }) => (
            <span className="text-gray-500 font-medium">
                {row.original.type_label ?? '-'}
            </span>
        ),
    },
    {
        accessorKey: 'report_date_formatted',
        header: 'Date',
        cell: ({ row }) => (
            <span className="text-gray-500 font-medium">
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
