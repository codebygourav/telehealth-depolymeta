import { cn } from '@/lib/utils';
import { useQueryClient } from '@tanstack/react-query';
import {
    AlertCircle,
    Ban,
    Calendar,
    CheckCircle2,
    Clock,
    Download,
    Eye,
    FileText,
    Filter,
    History,
    MoreHorizontal,
    Pause,
    Plus,
    Search,
    Syringe,
    Undo2,
    Upload,
    X
} from 'lucide-react';
import { useEffect, useState } from 'react';

import PaginationControls from '@/components/pagination/PaginationControls';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAssignVaccinationTemplate } from '@/mutations/assign-vaccination-template';
import { useMarkVaccinationComplete } from '@/mutations/mark-vaccination-complete';
import { useUpdatePatientVaccination } from '@/mutations/update-vaccination';
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
    const [completionTarget, setCompletionTarget] =
        useState<DoctorPatientVaccination | null>(null);
    const [completionRemark, setCompletionRemark] = useState('');
    const [completionDate, setCompletionDate] = useState(() => new Date().toISOString().slice(0, 10));
    const [completionImage, setCompletionImage] = useState<File | null>(null);
    const [assignmentDate] = useState(() => new Date().toISOString().slice(0, 10));
    const [filter, setFilter] = useState<'upcoming' | 'completed' | 'all'>('upcoming');
    const [searchTerm, setSearchTerm] = useState('');

    const [rescheduleTarget, setRescheduleTarget] = useState<DoctorPatientVaccination | null>(null);
    const [rescheduleDate, setRescheduleDate] = useState('');
    const [rescheduleNotes, setRescheduleNotes] = useState('');

    const [skipTarget, setSkipTarget] = useState<DoctorPatientVaccination | null>(null);
    const [skipReason, setSkipReason] = useState('');

    const [holdTarget, setHoldTarget] = useState<DoctorPatientVaccination | null>(null);
    const [holdReason, setHoldReason] = useState('');

    const [logsTarget, setLogsTarget] = useState<DoctorPatientVaccination | null>(null);
    const [detailsTarget, setDetailsTarget] = useState<DoctorPatientVaccination | null>(null);
    const [page, setPage] = useState(1);
    const perPage = 5;

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
    } = usePatientVaccinations(patientId, page, perPage, filter, searchTerm);

    const assignTemplateMutation = useAssignVaccinationTemplate();
    const markCompleteMutation = useMarkVaccinationComplete();
    const updateVaccinationMutation = useUpdatePatientVaccination();

    const pagination = patientVaccinationsResponse?.pagination;

    useEffect(() => {
        setPage(1);
    }, [patientId]);

    useEffect(() => {
        setPage(1);
    }, [filter, searchTerm]);

    const openRescheduleModal = (vaccination: DoctorPatientVaccination) => {
        setRescheduleTarget(vaccination);
        setRescheduleDate(vaccination.due_date || vaccination.scheduled_date || new Date().toISOString().slice(0, 10));
        setRescheduleNotes(vaccination.doctor_notes || '');
    };

    const openSkipModal = (vaccination: DoctorPatientVaccination) => {
        setSkipTarget(vaccination);
        setSkipReason(vaccination.skipped_reason || '');
    };

    const openHoldModal = (vaccination: DoctorPatientVaccination) => {
        setHoldTarget(vaccination);
        setHoldReason(vaccination.on_hold_reason || '');
    };

    const submitReschedule = async () => {
        if (!rescheduleTarget) return;
        try {
            await updateVaccinationMutation.mutateAsync({
                id: rescheduleTarget.id,
                body: {
                    due_date: rescheduleDate,
                    doctor_notes: rescheduleNotes,
                    status: 'rescheduled'
                }
            });
            await queryClient.invalidateQueries({ queryKey: ["patient-vaccinations", patientId] });
            setRescheduleTarget(null);
        } catch (err) {
            console.error('Failed to reschedule vaccination', err);
        }
    };

    const submitSkip = async () => {
        if (!skipTarget || !skipReason.trim()) return;
        try {
            await updateVaccinationMutation.mutateAsync({
                id: skipTarget.id,
                body: {
                    status: 'skipped_by_doctor',
                    skipped_reason: skipReason.trim()
                }
            });
            await queryClient.invalidateQueries({ queryKey: ["patient-vaccinations", patientId] });
            setSkipTarget(null);
            setSkipReason('');
        } catch (err) {
            console.error('Failed to skip vaccination', err);
        }
    };

    const submitHold = async () => {
        if (!holdTarget || !holdReason.trim()) return;
        try {
            await updateVaccinationMutation.mutateAsync({
                id: holdTarget.id,
                body: {
                    status: 'on_hold',
                    on_hold_reason: holdReason.trim()
                }
            });
            await queryClient.invalidateQueries({ queryKey: ["patient-vaccinations", patientId] });
            setHoldTarget(null);
            setHoldReason('');
        } catch (err) {
            console.error('Failed to place vaccination on hold', err);
        }
    };

    const patientVaccinations: DoctorPatientVaccination[] =
        patientVaccinationsResponse?.data ?? [];

    const isLoading = isTemplatesLoading || isPatientVaccinationsLoading;
    const error = templatesError || patientVaccinationsError;

    const handleAssignTemplate = async (template: ApiVaccinationTemplate) => {
        if (!patientId) {
            return;
        }

        if (assignedTemplateIds.has(template.id)) {
            setPreviewTemplate(null);
            return;
        }

        try {
            await assignTemplateMutation.mutateAsync({
                patientId,
                templateId: template.id,
                firstDoseDate: assignmentDate,
            });

            await queryClient.invalidateQueries({ queryKey: ["patient-vaccinations", patientId] });
            setIsTemplateModalOpen(false);
            setPreviewTemplate(null);
        } catch (assignError) {
            console.error("Failed to assign vaccination template:", assignError);
        }
    };

    const assignedTemplateIds = new Set(
        patientVaccinations
            .map((vaccination) => vaccination.vaccination_template_id)
            .filter(Boolean)
    );

    const activeTimelineStatuses = new Set([
        'pending',
        'scheduled',
        'upcoming',
        'due',
        'due_soon',
        'due_today',
        'overdue',
        'missed',
        'rescheduled',
        'on_hold',
        'skipped_by_doctor',
    ]);

    const resolvedStatusValue = (vaccination: DoctorPatientVaccination) => {
        const apiStatus = (vaccination.effective_status || vaccination.status || '').toLowerCase();
        if (apiStatus) {
            return apiStatus;
        }

        // Fallback only when API status is missing.
        if (vaccination.completed_date) {
            return 'completed';
        }

        return 'pending';
    };

    const filteredVaccines = patientVaccinations;

    const totalVaccines = pagination?.total ?? patientVaccinations.length;
    const upcomingCount = patientVaccinations.filter((v) => {
        const statusValue = resolvedStatusValue(v);
        return (
            activeTimelineStatuses.has(statusValue || 'pending')
        );
    }).length;
    const overdueCount = patientVaccinations.filter((v) =>
        resolvedStatusValue(v) === 'overdue' ||
        v.effective_status_label?.toLowerCase() === 'overdue'
    ).length;
    const completedCount = patientVaccinations.filter((v) =>
        resolvedStatusValue(v) === 'completed'
    ).length;

    const openCompletionModal = (vaccination: DoctorPatientVaccination) => {
        setCompletionTarget(vaccination);
        setCompletionRemark(vaccination.doctor_notes || '');
        setCompletionDate(vaccination.completed_date || new Date().toISOString().slice(0, 10));
        setCompletionImage(null);
    };

    const submitCompletion = async () => {
        if (!completionTarget) {
            return;
        }

        const body = new FormData();
        body.append('completed_date', completionDate);
        if (completionRemark.trim()) {
            body.append('doctor_notes', completionRemark.trim());
        }
        if (completionImage) {
            body.append('vaccination_image', completionImage);
        }

        try {
            await markCompleteMutation.mutateAsync({ id: completionTarget.id, body });
            await queryClient.invalidateQueries({ queryKey: ["patient-vaccinations", patientId] });
            setCompletionTarget(null);
        } catch (err) {
            console.error('Failed to mark vaccination complete', err);
        }
    };

    const statusBadgeClass = (status?: string | null) => {
        switch ((status || '').toLowerCase()) {
            case 'completed':
                return 'bg-green-50 text-green-700 border-green-200';
            case 'due':
            case 'due_soon':
            case 'due_today':
                return 'bg-orange-50 text-orange-700 border-orange-200';
            case 'overdue':
                return 'bg-red-50 text-red-700 border-red-200';
            case 'missed':
                return 'bg-rose-50 text-rose-700 border-rose-200';
            case 'scheduled':
            case 'rescheduled':
                return 'bg-blue-50 text-blue-700 border-blue-200';
            case 'on_hold':
                return 'bg-amber-50 text-amber-700 border-amber-200';
            case 'skipped_by_doctor':
                return 'bg-gray-100 text-gray-700 border-gray-300';
            case 'pending':
            case 'upcoming':
                return 'bg-yellow-50 text-yellow-700 border-yellow-200';
            default:
                return 'bg-gray-50 text-gray-700 border-gray-200';
        }
    };

    const prettyLabel = (value?: string | null) =>
        value
            ? value
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase())
            : '';

    const statusLabel = (vaccination: DoctorPatientVaccination) =>
        resolvedStatusValue(vaccination) === 'completed'
            ? 'Completed'
            :
            prettyLabel(vaccination.effective_status_label) ||
            vaccination.status_label ||
            prettyLabel(vaccination.effective_status) ||
            prettyLabel(vaccination.status) ||
            'Pending';

    const statusValue = (vaccination: DoctorPatientVaccination) =>
        resolvedStatusValue(vaccination);

    const displayDueDate = (vaccination: DoctorPatientVaccination) =>
        vaccination.due_date || vaccination.scheduled_date || vaccination.expected_date;

    const ruleLabel = (vaccination: DoctorPatientVaccination) => {
        const parts: string[] = [];

        if (vaccination.due_after_months && vaccination.due_after_months > 0) {
            parts.push(`${vaccination.due_after_months} month${vaccination.due_after_months === 1 ? '' : 's'}`);
        }

        if (vaccination.due_after_days && vaccination.due_after_days > 0) {
            parts.push(`${vaccination.due_after_days} day${vaccination.due_after_days === 1 ? '' : 's'}`);
        }

        if (parts.length === 0) {
            return vaccination.dose_no === 1 ? 'Assignment date' : 'Template base date';
        }

        return `After ${parts.join(' + ')}`;
    };

    const templateRuleLabel = (item: ApiVaccinationTemplate['items'][number]) => {
        if (item.timing_type === 'doctor_manual_date' || item.doctor_manual_date) {
            return 'Doctor manual date';
        }

        if (item.timing_type === 'previous_dose' || item.depends_on_previous_dose) {
            const value = item.interval_value ?? item.due_after_days ?? 0;
            const unit = item.interval_unit ?? 'days';
            return `${value} ${unit} after previous completed dose`;
        }

        if (item.offset_value !== undefined && item.offset_unit) {
            return `${item.offset_value} ${item.offset_unit} from base date`;
        }

        return item.recommended_age_label || 'Assignment date';
    };

    const addDurationToDate = (date: string, value: number, unit: string) => {
        const parsed = new Date(`${date}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) {
            return null;
        }

        if (unit === 'weeks') {
            parsed.setDate(parsed.getDate() + value * 7);
        } else if (unit === 'months') {
            parsed.setMonth(parsed.getMonth() + value);
        } else if (unit === 'years') {
            parsed.setFullYear(parsed.getFullYear() + value);
        } else {
            parsed.setDate(parsed.getDate() + value);
        }

        return parsed.toLocaleDateString(undefined, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    const templatePreviewDateLabel = (item: ApiVaccinationTemplate['items'][number]) => {
        if (item.timing_type === 'doctor_manual_date' || item.doctor_manual_date) {
            return 'Doctor will set date';
        }

        if (item.timing_type === 'previous_dose' || item.depends_on_previous_dose) {
            return 'Calculated after previous dose is completed';
        }

        return addDurationToDate(
            assignmentDate,
            item.offset_value ?? item.due_after_days ?? 0,
            item.offset_unit ?? 'days'
        ) ?? 'Calculated after assignment';
    };

    const formatDate = (date?: string | null) => {
        if (!date) {
            return 'N/A';
        }

        const parsed = new Date(`${date}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) {
            return date;
        }

        return parsed.toLocaleDateString(undefined, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    const formatDateTime = (value?: string | null) => {
        if (!value) {
            return 'N/A';
        }

        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        const day = String(parsed.getDate()).padStart(2, '0');
        const month = String(parsed.getMonth() + 1).padStart(2, '0');
        const year = parsed.getFullYear();
        const hours = String(parsed.getHours()).padStart(2, '0');
        const minutes = String(parsed.getMinutes()).padStart(2, '0');
        const seconds = String(parsed.getSeconds()).padStart(2, '0');

        return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    };

    const statusDateDescriptor = (vaccination: DoctorPatientVaccination) => {
        const sv = statusValue(vaccination);

        if (sv === 'completed' && vaccination.completed_date) {
            return { label: 'Completed', value: vaccination.completed_date, color: 'text-green-700' };
        }

        if (sv === 'rescheduled' && vaccination.changed_date) {
            return { label: 'Rescheduled', value: vaccination.changed_date, color: 'text-amber-700' };
        }

        if (sv === 'skipped_by_doctor' && (vaccination.changed_date || vaccination.due_date)) {
            return { label: 'Skipped', value: vaccination.changed_date || vaccination.due_date, color: 'text-gray-700' };
        }

        if (sv === 'on_hold' && (vaccination.changed_date || vaccination.due_date)) {
            return { label: 'On Hold', value: vaccination.changed_date || vaccination.due_date, color: 'text-amber-700' };
        }

        if (sv === 'missed' && vaccination.missed_date) {
            return { label: 'Missed', value: vaccination.missed_date, color: 'text-rose-700' };
        }

        if (sv === 'overdue' && vaccination.overdue_date) {
            return { label: 'Overdue', value: vaccination.overdue_date, color: 'text-red-700' };
        }

        return null;
    };

    const completionDeltaText = (vaccination: DoctorPatientVaccination) => {
        if (!vaccination.completed_date || !(vaccination.expected_date || vaccination.scheduled_date)) {
            return null;
        }

        const completed = new Date(`${vaccination.completed_date}T00:00:00`);
        const expectedRaw = vaccination.expected_date || vaccination.scheduled_date;
        const expected = expectedRaw ? new Date(`${expectedRaw}T00:00:00`) : null;

        if (!expected || Number.isNaN(completed.getTime()) || Number.isNaN(expected.getTime())) {
            return null;
        }

        const diffDays = Math.round((completed.getTime() - expected.getTime()) / (1000 * 60 * 60 * 24));

        if (diffDays === 0) {
            return 'Completed on expected date';
        }

        if (diffDays > 0) {
            return `Completed ${diffDays} day${diffDays === 1 ? '' : 's'} after expected date`;
        }

        const earlyDays = Math.abs(diffDays);
        return `Completed ${earlyDays} day${earlyDays === 1 ? '' : 's'} before expected date`;
    };

    const filterOptions: { value: typeof filter; label: string }[] = [
        { value: 'upcoming', label: 'Upcoming' },
        { value: 'completed', label: 'Completed' },
        { value: 'all', label: 'All' },
    ];

    if (!patientId) {
        return (
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                No appointment patient found for vaccination assignment. Vaccinations can only be managed for patients linked to this appointment.
            </div>
        );
    }

    return (
        <div className="space-y-8 animate-stagger-fade">
            {error && !isLoading && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    Unable to load vaccination data. Please refresh the page or try again later.
                </div>
            )}

            {/* Top Stats */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">

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

                {/* Completed */}
                <div className="rounded-lg p-4 sm:p-5 md:p-6 border bg-white shadow-sm">
                    <div className="flex items-center justify-between">

                        <div>
                            <p className="text-xs text-[#4D4D4D]">
                                Completed
                            </p>

                            <h3 className="text-2xl font-bold text-[#1F1E1E] mt-1">
                                {completedCount}
                            </h3>
                        </div>

                        <div className="flex items-center justify-center w-12 h-12 rounded-md bg-green-100">
                            <CheckCircle2 className="w-5 h-5 text-green-600" />
                        </div>
                    </div>
                </div>

            </div>

            {/* Main Actions & Table */}
            <div className="rounded-lg border bg-white p-4 sm:p-5 md:p-6 space-y-6 shadow-sm">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                    {/* Mobile: Buttons Top | Desktop: Search Left */}
                    <div className="order-2 md:order-1 flex flex-1 flex-col gap-3 md:max-w-2xl">
                        <div className="relative">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 opacity-60" />

                            <input
                                type="text"
                                placeholder="Search vaccines..."
                                className="w-full pl-12 pr-4 py-3 border rounded-md bg-white text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/20"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <span className="inline-flex items-center gap-2 text-xs font-semibold text-[#4D4D4D]">
                                <Filter className="h-4 w-4" />
                                Filter
                            </span>
                            {filterOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => setFilter(option.value)}
                                    className={cn(
                                        "rounded-md border px-3 py-1.5 text-xs font-semibold transition-colors",
                                        filter === option.value
                                            ? "border-primary bg-primary text-white"
                                            : "border-light-gray bg-white text-[#4D4D4D] hover:bg-gray-50"
                                    )}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>
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
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Vaccine / Dose</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Rule</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Due Date</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D]">Status</th>
                                <th className="px-6 py-4 text-xs font-semibold text-[#4D4D4D] text-right">Actions</th>
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
                                            <div className="w-full">
                                                <p className="font-semibold text-sm text-[#1F1E1E]">
                                                    {vaccination.vaccination?.name || "N/A"}
                                                </p>

                                                <p className="text-xs text-[#4D4D4D] mt-1">
                                                    Dose {vaccination.dose_no ?? "-"} - {vaccination.set_name || "General"}
                                                </p>

                                                {vaccination.vaccination?.disease_for && (
                                                    <p className="mt-1 max-w-full truncate text-[11px] font-medium text-[#808080]" title={vaccination.vaccination.disease_for}>
                                                        For: {vaccination.vaccination.disease_for}
                                                    </p>
                                                )}
                                            </div>
                                        </td>

                                        <td className="px-6 py-5">
                                            <div className="space-y-1 text-xs text-[#4D4D4D]">
                                                <p className="font-semibold text-[#1F1E1E]">{ruleLabel(vaccination)}</p>
                                                {vaccination.recommended_age_label && (
                                                    <p>{vaccination.recommended_age_label}</p>
                                                )}
                                                {(vaccination.grace_period_before_days || vaccination.grace_period_after_days) ? (
                                                    <p className="text-[11px]">
                                                        Grace: {vaccination.grace_period_before_days ?? 0}d before / {vaccination.grace_period_after_days ?? 0}d after
                                                    </p>
                                                ) : null}
                                            </div>
                                        </td>

                                        <td className="px-6 py-5">
                                            <div className="text-xs text-[#4D4D4D]">
                                                <p className="font-bold text-on-surface">{formatDate(displayDueDate(vaccination))}</p>
                                                {(() => {
                                                    const descriptor = statusDateDescriptor(vaccination);
                                                    if (!descriptor?.value) {
                                                        return null;
                                                    }

                                                    return (
                                                        <p className={`mt-1 text-[11px] ${descriptor.color}`}>
                                                            {descriptor.label}: {formatDate(descriptor.value)}
                                                        </p>
                                                    );
                                                })()}
                                            </div>
                                        </td>

                                        <td className="px-6 py-5">
                                            <div className="flex flex-col gap-1 items-start">
                                                <span className={cn("inline-flex rounded-md border px-3 py-1 text-xs font-semibold", statusBadgeClass(statusValue(vaccination)))}>
                                                    {statusLabel(vaccination)}
                                                </span>
                                                {statusValue(vaccination) === 'skipped_by_doctor' && vaccination.skipped_reason && (
                                                    <span className="text-[10px] text-gray-500 italic max-w-full truncate" title={vaccination.skipped_reason}>
                                                        Reason: {vaccination.skipped_reason}
                                                    </span>
                                                )}
                                                {statusValue(vaccination) === 'on_hold' && vaccination.on_hold_reason && (
                                                    <span className="text-[10px] text-gray-500 italic max-w-full truncate" title={vaccination.on_hold_reason}>
                                                        Reason: {vaccination.on_hold_reason}
                                                    </span>
                                                )}
                                                <span className="text-[10px] text-[#808080]">
                                                    Logs: {vaccination.logs?.length ?? 0}
                                                </span>
                                            </div>
                                        </td>

                                        <td className="px-6 py-5 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setDetailsTarget(vaccination)}
                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-md bg-primary/10 text-primary transition-all hover:bg-primary/15"
                                                    title="View Dose Details"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </button>

                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <button
                                                            type="button"
                                                            className="inline-flex h-9 w-9 items-center justify-center rounded-md border bg-white text-[#4D4D4D] transition-all hover:bg-gray-50"
                                                            title="More actions"
                                                        >
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-56">
                                                        <DropdownMenuItem onClick={() => setDetailsTarget(vaccination)}>
                                                            <Eye className="h-4 w-4 text-primary" />
                                                            View details
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem onClick={() => setLogsTarget(vaccination)}>
                                                            <History className="h-4 w-4 text-gray-600" />
                                                            View logs
                                                        </DropdownMenuItem>
                                                        {statusValue(vaccination) !== 'completed' && (
                                                            <>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem onClick={() => openCompletionModal(vaccination)}>
                                                                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                                                                    Mark completed
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem onClick={() => openRescheduleModal(vaccination)}>
                                                                    <Calendar className="h-4 w-4 text-blue-600" />
                                                                    Reschedule dose
                                                                </DropdownMenuItem>
                                                                {statusValue(vaccination) !== 'on_hold' && (
                                                                    <DropdownMenuItem onClick={() => openHoldModal(vaccination)}>
                                                                        <Pause className="h-4 w-4 text-amber-600" />
                                                                        Put on hold
                                                                    </DropdownMenuItem>
                                                                )}
                                                                {statusValue(vaccination) !== 'skipped_by_doctor' && (
                                                                    <DropdownMenuItem
                                                                        onClick={() => openSkipModal(vaccination)}
                                                                        variant="destructive"
                                                                    >
                                                                        <Ban className="h-4 w-4" />
                                                                        Skip dose
                                                                    </DropdownMenuItem>
                                                                )}
                                                            </>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
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

            {pagination && pagination.last_page > 1 && (
                <PaginationControls
                    currentPage={pagination.current_page}
                    totalPages={pagination.last_page}
                    totalItems={pagination.total}
                    itemsPerPage={pagination.per_page}
                    onPageChange={setPage}
                />
            )}

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
                                            <p className="text-sm font-semibold text-[#4D4D4D] mb-5">
                                                {assignedTemplateIds.has(previewTemplate.id)
                                                    ? "This template is already assigned."
                                                    : "Review the calculated dose dates before assigning this template."}
                                            </p>
                                            <div className="overflow-x-auto rounded-lg border">
                                                <table className="w-full text-left bg-white">
                                                    <thead className="bg-[#F8F8F8]">
                                                        <tr>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Vaccine</th>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Type</th>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Calculated Rule</th>
                                                            <th className="px-4 py-3 text-xs font-semibold text-[#4D4D4D]">Preview Date</th>
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

                                                                <td className="px-4 py-3">
                                                                    <div className="font-medium text-on-surface">{templateRuleLabel(item)}</div>
                                                                    {item.recommended_age_label && (
                                                                        <div className="text-xs text-[#888]">{item.recommended_age_label}</div>
                                                                    )}
                                                                </td>

                                                                <td className="px-4 py-3 font-medium text-[#1F1E1E]">
                                                                    {templatePreviewDateLabel(item)}
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
                                                disabled={assignedTemplateIds.has(previewTemplate.id) || assignTemplateMutation.isPending}
                                                className={cn(
                                                    "px-5 py-3 rounded-md text-sm font-semibold shadow-sm transition-all",
                                                    assignedTemplateIds.has(previewTemplate.id)
                                                        ? "cursor-not-allowed bg-gray-200 text-[#4D4D4D]"
                                                        : "bg-primary text-white hover:opacity-90"
                                                )}
                                            >
                                                {assignedTemplateIds.has(previewTemplate.id) ? "Already Assigned" : "Confirm & Assign Template"}
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    vaccinationTemplates?.data?.map((template: ApiVaccinationTemplate) => {
                                        const isAssigned = assignedTemplateIds.has(template.id);

                                        return (
                                            <div
                                                key={template.id}
                                                className={cn(
                                                    "rounded-lg border bg-white p-4 sm:p-5 md:p-6 shadow-sm flex flex-col justify-between",
                                                    isAssigned && "border-green-200 bg-green-50/40"
                                                )}
                                            >
                                                <div>
                                                    <div className="flex justify-between items-start mb-4">

                                                        <span className={cn(
                                                            "px-3 py-1 rounded-md text-xs font-semibold",
                                                            isAssigned ? "bg-green-100 text-green-700" : "bg-primary/10 text-primary"
                                                        )}>
                                                            {isAssigned ? "Already Assigned" : "Vaccination Plan"}
                                                        </span>

                                                        <span className="text-[10px] font-bold">
                                                            {formatDateTime(template.created_at)}
                                                        </span>
                                                    </div>

                                                    <h4 className="text-lg font-semibold text-[#1F1E1E]">
                                                        {template.name}
                                                    </h4>

                                                    <p className="text-xs font-medium text-[#4D4D4D] line-clamp-1">
                                                        {template.description}
                                                    </p>

                                                    <p className="text-xs font-medium mt-2">Total Vaccines: <span className="font-bold text-on-surface">{template.items?.length ?? 0}</span></p>
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
                                                        disabled={isAssigned || assignTemplateMutation.isPending}
                                                        className={cn(
                                                            "flex-1 py-3 px-4 rounded-md text-sm font-semibold flex items-center justify-center gap-2 shadow-sm transition-all",
                                                            isAssigned
                                                                ? "cursor-not-allowed bg-gray-200 text-[#4D4D4D]"
                                                                : "bg-primary text-white hover:opacity-90"
                                                        )}
                                                    >
                                                        {isAssigned ? "Assigned" : "Assign"}
                                                    </button>
                                                </div>
                                            </div>
                                        );
                                    })
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {completionTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        onClick={() => setCompletionTarget(null)}
                        className="absolute inset-0 bg-on-surface/20 backdrop-blur-sm"
                    />

                    <div className="relative w-full max-w-lg rounded-lg border bg-white shadow-sm">
                        <div className="flex items-center justify-between gap-3 border-b px-4 py-4 sm:px-5">
                            <div>
                                <h3 className="text-base font-semibold text-[#1F1E1E]">
                                    Mark Vaccination Completed
                                </h3>
                                <p className="text-xs font-medium text-[#4D4D4D]">
                                    {completionTarget.vaccination?.name || "Vaccination"}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setCompletionTarget(null)}
                                className="rounded-md border bg-white p-2 transition-all hover:bg-gray-50"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="space-y-4 p-4 sm:p-5">
                            <div className="grid grid-cols-1 gap-3 rounded-md border bg-gray-50 p-3 text-xs text-[#4D4D4D] sm:grid-cols-2">
                                <div>
                                    <span className="font-semibold text-[#1F1E1E]">Scheduled</span>
                                    <p>{formatDate(completionTarget.scheduled_date)}</p>
                                </div>
                                <div>
                                    <span className="font-semibold text-[#1F1E1E]">Age</span>
                                    <p>{completionTarget.patient_age_on_schedule || completionTarget.patient_age || "N/A"}</p>
                                </div>
                            </div>

                            <label className="block space-y-2">
                                <span className="text-xs font-semibold text-[#1F1E1E]">Completed Date</span>
                                <input
                                    type="date"
                                    value={completionDate}
                                    onChange={(event) => setCompletionDate(event.target.value)}
                                    className="w-full rounded-md border bg-white px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </label>

                            <label className="block space-y-2">
                                <span className="text-xs font-semibold text-[#1F1E1E]">Remark</span>
                                <textarea
                                    value={completionRemark}
                                    onChange={(event) => setCompletionRemark(event.target.value)}
                                    placeholder="Add doctor remark, reaction, or administration note"
                                    className="min-h-24 w-full rounded-md border bg-white px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </label>

                            <label className="block space-y-2">
                                <span className="text-xs font-semibold text-[#1F1E1E]">Upload Image</span>
                                <div className="flex items-center gap-3 rounded-md border border-dashed bg-white p-3">
                                    <Upload className="h-5 w-5 text-primary" />
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/jpg,image/webp"
                                        onChange={(event) => setCompletionImage(event.target.files?.[0] ?? null)}
                                        className="min-w-0 flex-1 text-xs font-medium text-[#4D4D4D]"
                                    />
                                </div>
                                {completionImage && (
                                    <p className="text-xs font-medium text-[#4D4D4D]">
                                        Selected: {completionImage.name}
                                    </p>
                                )}
                            </label>

                            {completionTarget.documents && completionTarget.documents.length > 0 && (
                                <div className="space-y-2 rounded-md border bg-gray-50 p-3">
                                    <p className="text-xs font-semibold text-[#1F1E1E]">Uploaded Documents</p>
                                    {completionTarget.documents.map((document) => (
                                        <a
                                            key={document.id}
                                            href={document.document_url || undefined}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="flex items-center gap-2 rounded-md border bg-white px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/5"
                                        >
                                            <FileText className="h-4 w-4" />
                                            View vaccination image
                                            {document.certificate_number ? ` (${document.certificate_number})` : ""}
                                        </a>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="flex justify-end gap-3 border-t px-4 py-4 sm:px-5">
                            <button
                                type="button"
                                onClick={() => setCompletionTarget(null)}
                                className="rounded-md border bg-white px-4 py-2 text-sm font-semibold text-[#4D4D4D] transition-all hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={submitCompletion}
                                disabled={markCompleteMutation.isPending}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {markCompleteMutation.isPending ? "Saving..." : "Complete Vaccination"}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Reschedule Modal */}
            {rescheduleTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        onClick={() => setRescheduleTarget(null)}
                        className="absolute inset-0 bg-on-surface/20 backdrop-blur-sm"
                    />

                    <div className="relative w-full max-w-lg rounded-lg border bg-white shadow-sm">
                        <div className="flex items-center justify-between gap-3 border-b px-4 py-4 sm:px-5">
                            <div>
                                <h3 className="text-base font-semibold text-[#1F1E1E]">
                                    Reschedule Vaccination Date
                                </h3>
                                <p className="text-xs font-medium text-[#4D4D4D]">
                                    {rescheduleTarget.vaccination?.name || "Vaccination"} - Dose {rescheduleTarget.dose_no}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setRescheduleTarget(null)}
                                className="rounded-md border bg-white p-2 transition-all hover:bg-gray-50"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="space-y-4 p-4 sm:p-5">
                            <label className="block space-y-2">
                                <span className="text-xs font-semibold text-[#1F1E1E]">New Due Date</span>
                                <input
                                    type="date"
                                    value={rescheduleDate}
                                    onChange={(event) => setRescheduleDate(event.target.value)}
                                    className="w-full rounded-md border bg-white px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </label>

                            <label className="block space-y-2">
                                <span className="text-xs font-semibold text-[#1F1E1E]">Doctor&apos;s Remark / Reason</span>
                                <textarea
                                    value={rescheduleNotes}
                                    onChange={(event) => setRescheduleNotes(event.target.value)}
                                    placeholder="Provide medical reason or context for rescheduling..."
                                    className="min-h-24 w-full rounded-md border bg-white px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </label>
                        </div>

                        <div className="flex justify-end gap-3 border-t px-4 py-4 sm:px-5">
                            <button
                                type="button"
                                onClick={() => setRescheduleTarget(null)}
                                className="rounded-md border bg-white px-4 py-2 text-sm font-semibold text-[#4D4D4D] transition-all hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={submitReschedule}
                                disabled={updateVaccinationMutation.isPending}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {updateVaccinationMutation.isPending ? "Saving..." : "Reschedule"}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Skip Modal */}
            {skipTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        onClick={() => setSkipTarget(null)}
                        className="absolute inset-0 bg-on-surface/20 backdrop-blur-sm"
                    />

                    <div className="relative w-full max-w-lg rounded-lg border bg-white shadow-sm">
                        <div className="flex items-center justify-between gap-3 border-b px-4 py-4 sm:px-5">
                            <div>
                                <h3 className="text-base font-semibold text-[#1F1E1E]">
                                    Skip Vaccination Dose
                                </h3>
                                <p className="text-xs font-medium text-[#4D4D4D]">
                                    {skipTarget.vaccination?.name || "Vaccination"} - Dose {skipTarget.dose_no}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setSkipTarget(null)}
                                className="rounded-md border bg-white p-2 transition-all hover:bg-gray-50"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="space-y-4 p-4 sm:p-5">
                            <label className="block space-y-2">
                                <span className="text-xs font-semibold text-[#1F1E1E]">Skipped Reason (Required)</span>
                                <textarea
                                    value={skipReason}
                                    onChange={(event) => setSkipReason(event.target.value)}
                                    placeholder="Specify medical reasons (e.g. contraindications, patient allergic reaction)..."
                                    className="min-h-24 w-full rounded-md border bg-white px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </label>
                        </div>

                        <div className="flex justify-end gap-3 border-t px-4 py-4 sm:px-5">
                            <button
                                type="button"
                                onClick={() => setSkipTarget(null)}
                                className="rounded-md border bg-white px-4 py-2 text-sm font-semibold text-[#4D4D4D] transition-all hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={submitSkip}
                                disabled={updateVaccinationMutation.isPending || !skipReason.trim()}
                                className="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {updateVaccinationMutation.isPending ? "Saving..." : "Skip Dose"}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Hold Modal */}
            {holdTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        onClick={() => setHoldTarget(null)}
                        className="absolute inset-0 bg-on-surface/20 backdrop-blur-sm"
                    />

                    <div className="relative w-full max-w-lg rounded-lg border bg-white shadow-sm">
                        <div className="flex items-center justify-between gap-3 border-b px-4 py-4 sm:px-5">
                            <div>
                                <h3 className="text-base font-semibold text-[#1F1E1E]">
                                    Place Vaccination On Hold
                                </h3>
                                <p className="text-xs font-medium text-[#4D4D4D]">
                                    {holdTarget.vaccination?.name || "Vaccination"} - Dose {holdTarget.dose_no}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setHoldTarget(null)}
                                className="rounded-md border bg-white p-2 transition-all hover:bg-gray-50"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="space-y-4 p-4 sm:p-5">
                            <label className="block space-y-2">
                                <span className="text-xs font-semibold text-[#1F1E1E]">Hold Reason (Required)</span>
                                <textarea
                                    value={holdReason}
                                    onChange={(event) => setHoldReason(event.target.value)}
                                    placeholder="Specify reason for pausing (e.g. temporary illness, fever, traveling)..."
                                    className="min-h-24 w-full rounded-md border bg-white px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </label>
                        </div>

                        <div className="flex justify-end gap-3 border-t px-4 py-4 sm:px-5">
                            <button
                                type="button"
                                onClick={() => setHoldTarget(null)}
                                className="rounded-md border bg-white px-4 py-2 text-sm font-semibold text-[#4D4D4D] transition-all hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={submitHold}
                                disabled={updateVaccinationMutation.isPending || !holdReason.trim()}
                                className="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {updateVaccinationMutation.isPending ? "Saving..." : "Place on Hold"}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Dose Details Modal */}
            {detailsTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        onClick={() => setDetailsTarget(null)}
                        className="absolute inset-0 bg-on-surface/20 backdrop-blur-sm"
                    />

                    <div className="relative w-full max-w-3xl rounded-lg border bg-white shadow-sm overflow-hidden flex flex-col max-h-[85vh]">
                        <div className="flex items-center justify-between gap-3 border-b px-4 py-4 sm:px-5 shrink-0">
                            <div>
                                <h3 className="text-base font-semibold text-[#1F1E1E]">
                                    Vaccination Dose Details
                                </h3>
                                <p className="text-xs font-medium text-[#4D4D4D]">
                                    {detailsTarget.vaccination?.name || "Vaccination"} - Dose {detailsTarget.dose_no ?? "-"}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setDetailsTarget(null)}
                                className="rounded-md border bg-white p-2 transition-all hover:bg-gray-50"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="flex-1 overflow-y-auto p-4 sm:p-5 space-y-5">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className={cn("inline-flex rounded-md border px-3 py-1 text-xs font-semibold", statusBadgeClass(statusValue(detailsTarget)))}>
                                    {statusLabel(detailsTarget)}
                                </span>
                                <span className="rounded-md bg-gray-100 px-3 py-1 text-xs font-semibold text-[#4D4D4D]">
                                    {detailsTarget.set_name || "General schedule"}
                                </span>
                                {detailsTarget.recommended_age_label && (
                                    <span className="rounded-md bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                        {detailsTarget.recommended_age_label}
                                    </span>
                                )}
                            </div>

                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div className="rounded-lg border bg-gray-50 p-4">
                                    <p className="text-xs font-semibold text-[#808080]">Rule</p>
                                    <p className="mt-1 text-sm font-bold text-[#1F1E1E]">{ruleLabel(detailsTarget)}</p>
                                    {(detailsTarget.grace_period_before_days || detailsTarget.grace_period_after_days) ? (
                                        <p className="mt-2 text-xs text-[#4D4D4D]">
                                            Grace: {detailsTarget.grace_period_before_days ?? 0}d before / {detailsTarget.grace_period_after_days ?? 0}d after
                                        </p>
                                    ) : null}
                                </div>

                                <div className="rounded-lg border bg-gray-50 p-4">
                                    <p className="text-xs font-semibold text-[#808080]">Patient Timing</p>
                                    <p className="mt-1 text-sm font-bold text-[#1F1E1E]">Due: {formatDate(displayDueDate(detailsTarget))}</p>
                                    {(detailsTarget.patient_age_on_schedule || detailsTarget.patient_age) && (
                                        <p className="mt-2 text-xs text-[#4D4D4D]">Age on schedule: {detailsTarget.patient_age_on_schedule || detailsTarget.patient_age}</p>
                                    )}
                                </div>
                            </div>

                            {/* Date timeline — only show rows that have actual values */}
                            {(() => {
                                const sv = statusValue(detailsTarget);
                                const isCompleted = sv === 'completed' || Boolean(detailsTarget.completed_date);
                                const isMissed = sv === 'missed' && !isCompleted;
                                const isOverdue = sv === 'overdue' && !isCompleted;
                                const statusDate = statusDateDescriptor(detailsTarget);
                                const rows = [
                                    ["Assigned", detailsTarget.assigned_date || detailsTarget.first_dose_date, "text-[#1F1E1E]"],
                                    ["Expected", detailsTarget.expected_date || detailsTarget.scheduled_date, "text-[#1F1E1E]"],
                                    ...(isCompleted ? [["Completed", detailsTarget.completed_date, "text-green-700"]] : []),
                                    ...(!isCompleted && statusDate ? [[statusDate.label, statusDate.value, statusDate.color]] : []),
                                    ...(isOverdue && !statusDate ? [["Overdue", detailsTarget.overdue_date, "text-red-600"]] : []),
                                    ...(isMissed && !statusDate ? [["Missed", detailsTarget.missed_date, "text-rose-700"]] : []),
                                ].filter(([, value]) => Boolean(value)) as Array<[string, string | null | undefined, string]>;
                                return rows.length > 0 ? (
                                    <div className={`grid gap-3 ${rows.length <= 2 ? 'grid-cols-2' : rows.length === 3 ? 'grid-cols-3' : 'grid-cols-2 sm:grid-cols-4'}`}>
                                        {rows.map(([label, value, colorClass]) => (
                                            <div key={label} className="rounded-lg border bg-white p-3">
                                                <p className="text-[11px] font-semibold text-[#808080]">{label}</p>
                                                <p className={`mt-1 text-xs font-bold ${colorClass}`}>{formatDate(value)}</p>
                                            </div>
                                        ))}
                                    </div>
                                ) : null;
                            })()}

                            <div className="rounded-lg border bg-white p-4">
                                <p className="text-xs font-semibold text-[#808080]">Doctor Updates</p>
                                <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div>
                                        <p className="text-[11px] font-semibold text-[#808080]">Changed Date</p>
                                        <p className="text-xs font-bold text-[#1F1E1E]">{formatDate(detailsTarget.changed_date)}</p>
                                    </div>
                                    <div>
                                        <p className="text-[11px] font-semibold text-[#808080]">Completed Date</p>
                                        <p className="text-xs font-bold text-[#1F1E1E]">{formatDate(detailsTarget.completed_date)}</p>
                                    </div>
                                    <div>
                                        <p className="text-[11px] font-semibold text-[#808080]">Audit Logs</p>
                                        <p className="text-xs font-bold text-[#1F1E1E]">{detailsTarget.logs?.length ?? 0}</p>
                                    </div>
                                </div>

                                {completionDeltaText(detailsTarget) && (
                                    <p className="mt-3 text-xs font-semibold text-green-700">
                                        {completionDeltaText(detailsTarget)}
                                    </p>
                                )}

                                {(detailsTarget.doctor_notes || detailsTarget.skipped_reason || detailsTarget.on_hold_reason) && (
                                    <div className="mt-3 space-y-2 text-xs text-[#4D4D4D]">
                                        {detailsTarget.doctor_notes && <p><span className="font-semibold text-[#1F1E1E]">Remark:</span> {detailsTarget.doctor_notes}</p>}
                                        {detailsTarget.skipped_reason && <p><span className="font-semibold text-[#1F1E1E]">Skipped reason:</span> {detailsTarget.skipped_reason}</p>}
                                        {detailsTarget.on_hold_reason && <p><span className="font-semibold text-[#1F1E1E]">On hold reason:</span> {detailsTarget.on_hold_reason}</p>}
                                    </div>
                                )}
                            </div>

                            <div className="rounded-lg border bg-white p-4">
                                <p className="text-xs font-semibold text-[#808080]">Documents / Certificates</p>
                                {detailsTarget.documents && detailsTarget.documents.length > 0 ? (
                                    <div className="mt-3 space-y-2">
                                        {detailsTarget.documents.map((document) => (
                                            <a
                                                key={document.id}
                                                href={document.document_url || document.document || "#"}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="flex items-center justify-between rounded-md border bg-gray-50 px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/5"
                                            >
                                                <span>{document.document_type || "Document"} {document.certificate_number ? `#${document.certificate_number}` : ""}</span>
                                                <Download className="h-4 w-4" />
                                            </a>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="mt-2 text-xs text-[#808080]">No documents attached yet.</p>
                                )}
                            </div>
                        </div>

                        <div className="flex justify-end gap-3 border-t px-4 py-4 sm:px-5 shrink-0">
                            <button
                                type="button"
                                onClick={() => {
                                    setLogsTarget(detailsTarget);
                                    setDetailsTarget(null);
                                }}
                                className="rounded-md border bg-white px-4 py-2 text-sm font-semibold text-[#4D4D4D] transition-all hover:bg-gray-50"
                            >
                                View Logs
                            </button>
                            <button
                                type="button"
                                onClick={() => setDetailsTarget(null)}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:opacity-90"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Logs Modal */}
            {logsTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        onClick={() => setLogsTarget(null)}
                        className="absolute inset-0 bg-on-surface/20 backdrop-blur-sm"
                    />

                    <div className="relative w-full max-w-2xl rounded-lg border bg-white shadow-sm overflow-hidden flex flex-col max-h-[80vh]">
                        <div className="flex items-center justify-between gap-3 border-b px-4 py-4 sm:px-5 shrink-0">
                            <div>
                                <h3 className="text-base font-semibold text-[#1F1E1E]">
                                    Dose Audit History Logs
                                </h3>
                                <p className="text-xs font-medium text-[#4D4D4D]">
                                    {logsTarget.vaccination?.name || "Vaccination"} - Dose {logsTarget.dose_no}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setLogsTarget(null)}
                                className="rounded-md border bg-white p-2 transition-all hover:bg-gray-50"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
                            {logsTarget.logs && logsTarget.logs.length > 0 ? (
                                <div className="relative border-l border-gray-200 ml-3 space-y-6 pb-2">
                                    {logsTarget.logs.map((log) => (
                                        <div key={log.id} className="relative pl-6">
                                            {/* Dot */}
                                            <div className="absolute left-0 top-1.5 w-3 h-3 rounded-full bg-white border-2 border-primary flex items-center justify-center"></div>

                                            <div className="text-xs text-[#4D4D4D]">
                                                <span className="font-bold text-[#1F1E1E]">
                                                    {log.performed_by ? log.performed_by.name : 'System'}
                                                </span>
                                                {" performed "}
                                                <span className="font-semibold text-primary">
                                                    {log.action.replace('updated_', 'updated ')}
                                                </span>
                                                {" on "}
                                                <span className="font-medium text-[#1F1E1E]">
                                                    {new Date(log.created_at).toLocaleString()}
                                                </span>
                                            </div>

                                            <div className="mt-1.5 text-xs text-[#4D4D4D] space-y-1 bg-gray-50 p-2.5 rounded-lg border">
                                                {log.old_value !== null && log.old_value !== '' && (
                                                    <p>Old: <span className="font-mono bg-white px-1.5 py-0.5 rounded border">{log.old_value}</span></p>
                                                )}
                                                {log.new_value !== null && log.new_value !== '' && (
                                                    <p>New: <span className="font-mono bg-white px-1.5 py-0.5 rounded border">{log.new_value}</span></p>
                                                )}
                                                {log.reason && (
                                                    <p className="italic text-[#808080] mt-1">Remark: &quot;{log.reason}&quot;</p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-xs text-[#4D4D4D] italic">
                                    No history logs recorded yet for this dose.
                                </div>
                            )}
                        </div>

                        <div className="flex justify-end border-t px-4 py-4 sm:px-5 shrink-0">
                            <button
                                type="button"
                                onClick={() => setLogsTarget(null)}
                                className="rounded-md border bg-white px-4 py-2 text-sm font-semibold text-[#4D4D4D] transition-all hover:bg-gray-50"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
