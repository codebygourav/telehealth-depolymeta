'use client';

import React, { useState, useMemo } from 'react';
import {
    Plus,
} from 'lucide-react';
import { useMedicalReports } from '@/queries/useGetMedicalReports';
import { useAuth } from '@/context/userContext';
import { DataTable } from '@/components/custom/data-table';
import { medicalRecordsColumns } from './column';
import { UploadReportModal } from '@/components/pages/medical-records/UploadReportModal';

const MedicalRecordsPage = () => {
    const { user } = useAuth();
    const [page, setPage] = useState(1);
    const [searchInput, setSearchInput] = useState('');
    const [status, setStatus] = useState<string>('all');
    const [reportType, setReportType] = useState<string>('all');

    const { data, isLoading } = useMedicalReports(user?.id, page);
    const records = data?.data ?? [];
    const pagination = data?.pagination;

    // Filter across search, status and type
    const filtered = useMemo(() => {
        return records.filter((r) => {
            const matchesSearch = [r.report_name, r.type_label, r.doctor?.name, r.status]
                .join(' ')
                .toLowerCase()
                .includes(searchInput.toLowerCase());

            const matchesStatus = status === 'all' || r.status.toLowerCase() === status.toLowerCase();
            const matchesType = reportType === 'all' || r.type_label.toLowerCase() === reportType.toLowerCase();

            return matchesSearch && matchesStatus && matchesType;
        });
    }, [records, searchInput, status, reportType]);

    // Dynamic options derived from API records
    const statusOptions = useMemo(() => {
        return Array.from(new Set(records.map(r => r.status))).map(s => ({
            label: s.charAt(0) + s.slice(1).toLowerCase(),
            value: s.toLowerCase(),
        }));
    }, [records]);

    const typeOptions = useMemo(() => {
        return Array.from(new Set(records.map(r => r.type_label))).map(t => ({
            label: t,
            value: t.toLowerCase(),
        }));
    }, [records]);


    // Upload modal state
    const [isModalOpen, setIsModalOpen] = useState(false);

    return (
        <div className="space-y-8 max-w-6xl mx-auto pb-12">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div className="space-y-1.5 sm:space-y-2 md:space-y-3">
                    <h1 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-black text-[#0A2E1F] tracking-tight">
                        Medical Records
                    </h1>
                    <p className="text-sm sm:text-base md:text-lg lg:text-xl font-medium text-gray-500">
                        Access your clinical history, lab results, and prescriptions.
                    </p>
                </div>
                <button
                    onClick={() => setIsModalOpen(true)}
                    className="flex items-center gap-2 bg-[#0A2E1F] text-white px-6 py-3.5 rounded-xl font-bold hover:bg-opacity-90 transition-all shadow-sm"
                >
                    <Plus className="w-5 h-5" />
                    <span>Upload New Report</span>
                </button>
            </div>

            {/* DataTable */}
            <DataTable
                columns={medicalRecordsColumns}
                data={filtered}
                loading={isLoading}
                pageCount={pagination?.last_page ?? 1}
                currentPage={page}
                totalItems={pagination?.total ?? 0}
                itemsPerPage={pagination?.per_page ?? 20}
                onPageChange={(p) => setPage(p)}
                enableSearch={true}
                searchValue={searchInput}
                onSearch={(val) => {
                    setSearchInput(val);
                    setPage(1);
                }}
                filters={[
                    {
                        column: 'status',
                        label: 'Status',
                        value: status,
                        onChange: (val) => {
                            setStatus(val);
                            setPage(1);
                        },
                        options: statusOptions,
                    },
                    {
                        column: 'report_type',
                        label: 'Type',
                        value: reportType,
                        onChange: (val) => {
                            setReportType(val);
                            setPage(1);
                        },
                        options: typeOptions,
                    },
                ]}
                onClearFilters={() => {
                    setStatus('all');
                    setReportType('all');
                    setSearchInput('');
                    setPage(1);
                }}
            />

            {/* Upload Modal - Logic extracted to component */}
            <UploadReportModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
            />
        </div>
    );
};

export default MedicalRecordsPage;