import { cn } from '@/lib/utils';
import { useQueryClient } from '@tanstack/react-query';
import {
    AlertCircle,
    CheckCircle2,
    Clock,
    Download,
    Eye,
    FileText,
    Plus,
    Search,
    Syringe,
    Undo2
} from 'lucide-react';
import { useState } from 'react';

import { useAssignVaccinationTemplate } from '@/mutations/assign-vaccination-template';
import { useMarkVaccinationComplete } from '@/mutations/mark-vaccination-complete';
import { usePatientVaccinations } from '@/queries/usePatientVaccinations';
import { useVaccinationTemplates } from "@/queries/useVaccinationTemplates";
import { DoctorPatientVaccination } from '@/types/patient-vaccination';
import { ApiVaccinationTemplate } from '@/types/vaccination-template';

interface VaccinationManagementProps {
    patientId?: string;
}

export function VaccinationManagement({ patientId }: VaccinationManagementProps) {
    const [isTemplateModalOpen, setIsTemplateModalOpen] = useState(false);
    const [previewTemplate, setPreviewTemplate] =
        useState<ApiVaccinationTemplate | null>(null);
    const [searchTerm, setSearchTerm] = useState('');
    const statusFilter = 'All';

    const queryClient = useQueryClient();

    const {
        data: vaccinationTemplates,
        isLoading: isTemplatesLoading,
        error: templatesError,
    } = useVaccinationTemplates();

    const {
        data: patientVaccinationsResponse,
        isLoading: isPatientVaccinationsLoading,
        error: patientVaccinationsError,
    } = usePatientVaccinations(patientId);

    const assignTemplateMutation = useAssignVaccinationTemplate();
    const markCompleteMutation = useMarkVaccinationComplete();

    const patientVaccinations: DoctorPatientVaccination[] =
        patientVaccinationsResponse?.data ?? [];

    const isLoading = isTemplatesLoading || isPatientVaccinationsLoading;
    const error = templatesError || patientVaccinationsError;

    const handleAssignTemplate = async (template: ApiVaccinationTemplate) => {
        if (!patientId) {
            return;
        }

        try {
            await assignTemplateMutation.mutateAsync({
                patientId,
                templateId: template.id,
            });

            await queryClient.invalidateQueries(["patient-vaccinations", patientId]);
            setIsTemplateModalOpen(false);
            setPreviewTemplate(null);
        } catch (assignError) {
            console.error("Failed to assign vaccination template:", assignError);
        }
    };

    const filteredVaccines = patientVaccinations.filter((v) => {
        const matchesSearch =
            v.vaccination?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ?? false;
        const statusValue = v.status?.toLowerCase();
        const matchesStatus =
            statusFilter === 'All' ||
            statusValue === statusFilter.toLowerCase() ||
            v.status_label?.toLowerCase() === statusFilter.toLowerCase();

        return matchesSearch && matchesStatus;
    });

    const totalVaccines = patientVaccinations.length;
    const upcomingCount = patientVaccinations.filter((v) => {
        const statusValue = v.status?.toLowerCase();
        return (
            statusValue === 'pending' ||
            statusValue === 'scheduled' ||
            statusValue == null
        );
    }).length;
    const overdueCount = patientVaccinations.filter((v) =>
        v.status?.toLowerCase() === 'overdue' ||
        v.status_label?.toLowerCase() === 'overdue'
    ).length;

    return (
        <div className="space-y-8 animate-stagger-fade">
            {error && !isLoading && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    Unable to load vaccination data. Please refresh the page or try again later.
                </div>
            )}

            {/* Top Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">

                {/* Total Vaccines */}
                <div className="rounded-lg p-4 sm:p-5 md:p-6 border bg-white shadow-sm">
                    <div className="flex items-center justify-between">

                        <div>
                            <p className="text-xs text-[#4D4D4D]">
                                Total Vaccines
                            </p>

                            <h3 className="text-2xl font-bold text-[#1F1E1E] mt-1">
                                {totalVaccines}
                            </h3>
                        </div>

                        <div className="flex items-center justify-center w-12 h-12 rounded-md bg-primary/10">
                            <Syringe className="w-5 h-5 text-primary" />
                        </div>
                    </div>
                </div>

                {/* Upcoming */}
                <div className="rounded-lg p-4 sm:p-5 md:p-6 border bg-white shadow-sm">
                    <div className="flex items-center justify-between">

                        <div>
                            <p className="text-xs text-[#4D4D4D]">
                                Upcoming
                            </p>

                            <h3 className="text-2xl font-bold text-[#1F1E1E] mt-1">
                                {upcomingCount}
                            </h3>
                        </div>

                        <div className="flex items-center justify-center w-12 h-12 rounded-md bg-yellow-100">
                            <Clock className="w-5 h-5 text-yellow-600" />
                        </div>
                    </div>
                </div>

                {/* Overdue */}
                <div className="rounded-lg p-4 sm:p-5 md:p-6 border bg-white shadow-sm">
                    <div className="flex items-center justify-between">

                        <div>
                            <p className="text-xs text-[#4D4D4D]">
                                Overdue
                            </p>

                            <h3 className="text-2xl font-bold text-[#1F1E1E] mt-1">
                                {overdueCount}
                            </h3>
                        </div>

                        <div className="flex items-center justify-center w-12 h-12 rounded-md bg-red-100">
                            <AlertCircle className="w-5 h-5 text-red-600" />
                        </div>
                    </div>
                </div>

            </div>

            {/* Main Actions & Table */}
            <div className="rounded-lg border bg-white p-4 sm:p-5 md:p-6 space-y-6 shadow-sm">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                    {/* Mobile: Buttons Top | Desktop: Search Left */}
                    <div className="order-2 md:order-1 flex-1 max-w-full md:max-w-md relative">
                        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 opacity-60" />

                        <input
                            type="text"
                            placeholder="Search vaccines..."
                            className="w-full pl-12 pr-4 py-3 border rounded-md bg-white text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/20"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>

                    {/* Mobile: Top Row Buttons */}
                    <div className="order-1 md:order-2 flex items-center justify-between md:justify-end gap-3">
                        <button
                            onClick={() => setIsTemplateModalOpen(true)}
                            className="bg-primary text-white px-4 sm:px-5 py-3 rounded-md text-sm font-semibold flex items-center gap-2 shadow-sm transition-all hover:opacity-90"
                        >
                            <Plus className="w-4 h-4 shrink-0" />
                            <span className="whitespace-nowrap">
                                Assign Template
                            </span>
                        </button>

                        <button className="p-3 border rounded-md bg-white shadow-sm hover:bg-gray-50 transition-all">
                            <Download className="w-5 h-5 text-[#4D4D4D]" />
                        </button>
                    </div>
                </div>

                {/* Advanced Clinical Table */}
                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-surface-container-low border-b border-outline-variant/30">
                            <tr>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Vaccine Name</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Type</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Scheduled</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Status</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-outline-variant/10">
                            {isLoading ? (
                                <tr>
                                    <td colSpan={5} className="px-6 py-20 text-center">
                                        <div className="flex flex-col items-center justify-center gap-3">
                                            <div className="h-10 w-10 rounded-full border-4 border-primary/20 border-t-primary animate-spin" />

                                            <p className="text-sm font-semibold text-[#4D4D4D]">
                                                Loading vaccinations...
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            ) : filteredVaccines.length > 0 ? (
                                filteredVaccines.map((vaccination: DoctorPatientVaccination) => (
                                    <tr
                                        key={vaccination.id}
                                        className="hover:bg-primary/5 transition-colors group"
                                    >
                                        <td className="px-6 py-5">
                                            <p className="font-semibold text-sm text-[#1F1E1E]">
                                                {vaccination.vaccination?.name || "N/A"}
                                            </p>

                                            <p className="text-xs text-[#4D4D4D] mt-1">
                                                Dose {vaccination.dose_no ?? "-"}
                                            </p>
                                        </td>

                                        <td className="px-6 py-5 text-[10px] font-black uppercase tracking-widest">
                                            {vaccination.set_name || "General"}
                                        </td>

                                        <td className="px-6 py-5 text-sm font-bold text-on-surface">
                                            {vaccination.scheduled_date || vaccination.recommended_age_label || "N/A"}
                                        </td>

                                        <td className="px-6 py-5">
                                            <span className="px-3 py-1 rounded-md text-xs font-semibold bg-primary/10 text-primary">
                                                {vaccination.status_label || vaccination.status || "Pending"}
                                            </span>
                                        </td>

                                        <td className="px-6 py-5 text-right">
                                            <button
                                                onClick={async () => {
                                                    try {
                                                        await markCompleteMutation.mutateAsync({ id: vaccination.id });
                                                        await queryClient.invalidateQueries(["patient-vaccinations", patientId]);
                                                    } catch (err) {
                                                        console.error('Failed to mark vaccination complete', err);
                                                    }
                                                }}
                                                className="p-2 text-primary hover:bg-primary/10 rounded-md transition-all"
                                            >
                                                <CheckCircle2 className="w-6 h-6" />
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={5} className="px-6 py-20 text-center">
                                        <div className="flex flex-col items-center gap-3 opacity-50">
                                            <FileText className="w-12 h-12" />

                                            <p className="text-sm font-medium text-[#4D4D4D]">
                                                No vaccinations assigned yet.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Notification Workflow UI */}
            <div className="rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm">
                <h4 className="text-sm font-semibold text-[#1F1E1E] mb-5">
                    Automated Notification Workflow
                </h4>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {[
                        {
                            step: 1,
                            label: "Vaccine Assigned",
                            msg: "System automatically notifies parent via SMS & Push Notification with the full schedule.",
                            icon: CheckCircle2,
                            color: "text-green-600",
                            bg: "bg-green-100",
                        },
                        {
                            step: 2,
                            label: "Upcoming Reminder",
                            msg: "Alert sent to parent 3 days and 1 day before the scheduled due date for booking.",
                            icon: Clock,
                            color: "text-primary",
                            bg: "bg-primary/10",
                        },
                        {
                            step: 3,
                            label: "Missed / Overdue Alert",
                            msg: "Immediate high-priority notification to both parent and doctor if status shifts to overdue.",
                            icon: AlertCircle,
                            color: "text-red-600",
                            bg: "bg-red-100",
                        },
                    ].map((item) => (
                        <div
                            key={item.step}
                            className="rounded-lg border bg-white p-4 shadow-sm"
                        >
                            <div className="flex items-start gap-3">
                                <div
                                    className={cn(
                                        "w-10 h-10 rounded-md flex items-center justify-center shrink-0",
                                        item.bg,
                                        item.color
                                    )}
                                >
                                    <item.icon className="w-5 h-5" />
                                </div>

                                <div>
                                    <p className="text-sm font-semibold text-[#1F1E1E]">
                                        {item.label}
                                    </p>

                                    <p className="text-xs text-[#4D4D4D] mt-1 leading-relaxed">
                                        {item.msg}
                                    </p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Template Assignment Modal */}
            <div>
                {isTemplateModalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <div

                            onClick={() => setIsTemplateModalOpen(false)}
                            className="absolute inset-0 bg-on-surface/20 backdrop-blur-sm"
                        />
                        <div

                            className="relative w-full max-w-4xl bg-white rounded-lg border shadow-sm overflow-hidden flex flex-col max-h-[90vh]" >
                            <div className="px-4 sm:px-5 md:px-6 py-4 border-b bg-white flex items-center justify-between gap-3">
                                <h3 className="text-base sm:text-lg font-semibold text-[#1F1E1E]">
                                    Assign Vaccination Template
                                </h3>

                                {!previewTemplate ? (
                                    <button
                                        onClick={() => setIsTemplateModalOpen(false)}
                                        className="p-2 rounded-md border bg-white hover:bg-gray-50 transition-all shrink-0"
                                    >
                                        <Plus className="w-5 h-5 sm:w-6 sm:h-6 rotate-45" />
                                    </button>
                                ) : (
                                    <button
                                        onClick={() => setPreviewTemplate(null)}
                                        className="flex items-center gap-2 text-primary font-semibold text-[10px] sm:text-xs uppercase tracking-wide hover:translate-x-1 transition-transform shrink-0"
                                    >
                                        <Undo2 className="w-4 h-4 shrink-0" />
                                        <span className="whitespace-nowrap">
                                            Back to Templates
                                        </span>
                                    </button>
                                )}
                            </div>

                            <div className="flex-1 overflow-y-auto p-4 sm:p-5 md:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                {previewTemplate ? (
                                    <div className="col-span-full space-y-6">

                                        <div className="rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm">
                                            <h4 className="text-xl font-bold text-[#1F1E1E] mb-1">{previewTemplate.name}</h4>
                                            <p className="text-sm font-semibold  mb-8">Clinical Roadmap Preview</p>

                                            <div className="overflow-x-auto rounded-lg border">
                                                <table className="w-full text-left bg-white">
                                                    <thead className="bg-[#F8F8F8]">
                                                        <tr>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Vaccine</th>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Type</th>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Month</th>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Dosage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-outline-variant/20">
                                                        {previewTemplate.items?.map((item) => (
                                                            <tr
                                                                key={item.id}
                                                                className="text-sm text-[#4D4D4D]"
                                                            >
                                                                {/* Vaccine */}
                                                                <td className="px-4 py-3">
                                                                    {item.vaccination?.name}
                                                                </td>

                                                                {/* Type */}
                                                                <td className="px-4 py-3">
                                                                    {item.set_name}
                                                                </td>

                                                                {/* Month */}
                                                                <td className="px-4 py-3">
                                                                    {item.recommended_age_label}
                                                                </td>

                                                                {/* Dosage */}
                                                                <td className="px-4 py-3">
                                                                    Dose {item.dose_no}
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div className="flex justify-end pt-4">
                                            <button
                                                onClick={() => handleAssignTemplate(previewTemplate)}
                                                className="bg-primary text-white px-5 py-3 rounded-md text-sm font-semibold shadow-sm hover:opacity-90 transition-all"                                            >
                                                Confirm & Assign Template
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    vaccinationTemplates?.data?.map((template: ApiVaccinationTemplate) => (
                                        <div
                                            key={template.id}
                                            className="rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm flex flex-col justify-between"
                                        >
                                            <div>
                                                <div className="flex justify-between items-start mb-4">

                                                    <span className="bg-primary/10 text-primary px-3 py-1 rounded-md text-xs font-semibold">
                                                        Vaccination Plan
                                                    </span>

                                                    <span className="text-[10px] font-bold">
                                                        {template.created_at}
                                                    </span>
                                                </div>

                                                <h4 className="text-lg font-semibold text-[#1F1E1E]">
                                                    {template.name}
                                                </h4>

                                                <p className="text-xs font-medium text-[#4D4D4D] line-clamp-1">
                                                    {template.description}
                                                </p>

                                                <p className="text-xs font-medium mt-2">Total Vaccines: <span className="font-bold text-on-surface">56</span></p>
                                            </div>

                                            <div className="flex items-center gap-3 mt-8">
                                                <button
                                                    onClick={() => setPreviewTemplate(template)}
                                                    className="flex-1 py-3 px-4 border rounded-md bg-white text-sm font-medium flex items-center justify-center gap-2 hover:bg-gray-50 transition-all"
                                                >
                                                    <Eye className="w-4 h-4" />
                                                    Preview
                                                </button>

                                                <button
                                                    onClick={() => handleAssignTemplate(template)}
                                                    className="flex-1 py-3 px-4 bg-primary text-white rounded-md text-sm font-semibold flex items-center justify-center gap-2 shadow-sm hover:opacity-90 transition-all"
                                                >
                                                    Assign
                                                </button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
