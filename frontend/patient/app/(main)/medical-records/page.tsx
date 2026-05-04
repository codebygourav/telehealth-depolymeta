'use client';

import React, { useState, useMemo } from 'react';
import {
    CloudUpload,
    Plus,
    Upload,
} from 'lucide-react';
import { useMedicalReports } from '@/queries/useGetMedicalReports';
import { useAuth } from '@/context/userContext';
import { DataTable } from '@/components/custom/data-table';
import { medicalRecordsColumns } from './column';
import { UploadReportModal } from '@/components/pages/medical-records/UploadReportModal';
import HeroSection from '@/components/hero-section';
import { Button } from '@/components/ui/button';

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
        <div className="pb-12 mx-auto space-y-8">

            <HeroSection title="Medical Records" description="Access your clinical history, lab results, and prescriptions." />

            <div className="flex items-center justify-end container-max-width mx-auto w-full">
                <Button
                    onClick={() => setIsModalOpen(true)}
                    className="flex items-center justify-center h-12 font-bold global-radius btn-primary-cta"
                >
                    <Upload className="w-5 h-5" />
                    <span>Upload New Report</span>
                </Button>
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
                        label: 'Report Type',
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
