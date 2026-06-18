import { CustomPagination } from "@/components/custom/CustomPagination";
import { Card } from "@/components/ui";
import { cn } from "@/lib/utils";
import { usePatientVaccinations } from "@/queries/usePatientVaccinations";
import type { ScheduleVaccination, VaccinationScheduleSet as VaccinationScheduleSetType } from "@/types/patient-vaccination";
import { AlertCircle, Calendar, CheckCircle2, ChevronDown, ChevronUp, Clock, Eye, Filter, X } from "lucide-react";
import { AnimatePresence, motion } from "motion/react";
import { useState } from "react";
import { createPortal } from 'react-dom';

type VaccinationDocument = NonNullable<ScheduleVaccination["documents"]>[number];

interface VaccineRowProps {
    name: string;
    desc?: string;
    age?: string;
    due?: string;
    assigned?: string;
    patientAge?: string | null;
    status?: string;
    effectiveStatus?: string;
    information?: ScheduleVaccination["information"];
    doctorNotes?: string | null;
    documents?: VaccinationDocument[];
    skippedReason?: string | null;
    onHoldReason?: string | null;
    changedDate?: string | null;
    expectedDate?: string | null;
    completedDate?: string | null;
    overdueDate?: string | null;
    missedDate?: string | null;
    gracePeriodBeforeDays?: number | null;
    gracePeriodAfterDays?: number | null;
    dueAfterMonths?: number | null;
    dueAfterDays?: number | null;
    doseNo?: number;
}

// vaccination table rows
function VaccineRow({
    name,
    desc,
    age,
    due,
    assigned,
    patientAge,
    status,
    effectiveStatus,
    information,
    doctorNotes,
    documents = [],
    skippedReason,
    onHoldReason,
    changedDate,
    expectedDate,
    completedDate,
    overdueDate,
    missedDate,
    gracePeriodBeforeDays,
    gracePeriodAfterDays,
    dueAfterMonths,
    dueAfterDays,
    doseNo,
}: VaccineRowProps) {

    const [open, setOpen] = useState(false);
    const canUsePortal = typeof document !== "undefined";

    // Build a human-readable scheduling rule
    const schedulingRule = (() => {
        const parts: string[] = [];
        if (dueAfterMonths && dueAfterMonths > 0) parts.push(`${dueAfterMonths} month${dueAfterMonths === 1 ? "" : "s"}`);
        if (dueAfterDays && dueAfterDays > 0) parts.push(`${dueAfterDays} day${dueAfterDays === 1 ? "" : "s"}`);
        if (parts.length === 0) return doseNo === 1 ? "At assignment / immediately" : null;
        return `After ${parts.join(" + ")}`;
    })();

    const formatDate = (value?: string | null) => {
        if (!value) return null;

        const parsed = new Date(`${value}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) return value;

        return parsed.toLocaleDateString(undefined, {
            day: "2-digit",
            month: "short",
            year: "numeric",
        });
    };

    const apiStatus = (effectiveStatus || status || "").toLowerCase();
    const normalizedStatus = apiStatus || (completedDate ? "completed" : "pending");
    const displayStatus = (status || normalizedStatus)
        .replace(/_/g, " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

    const statusDateDescriptor = (() => {
        if (normalizedStatus === "completed" && completedDate) {
            return { label: "Completed Date", value: completedDate, colorClass: "text-green-700" };
        }

        if (normalizedStatus === "rescheduled" && changedDate) {
            return { label: "Rescheduled Date", value: changedDate, colorClass: "text-amber-600" };
        }

        if (normalizedStatus === "skipped_by_doctor") {
            return { label: "Skipped On", value: changedDate || due || assigned, colorClass: "text-gray-700" };
        }

        if (normalizedStatus === "on_hold") {
            return { label: "On Hold Since", value: changedDate || due || assigned, colorClass: "text-amber-700" };
        }

        if (normalizedStatus === "missed" && missedDate) {
            return { label: "Missed Date", value: missedDate, colorClass: "text-rose-700" };
        }

        if (normalizedStatus === "overdue" && overdueDate) {
            return { label: "Overdue Since", value: overdueDate, colorClass: "text-red-600" };
        }

        return null;
    })();

    const completionDeltaText = (() => {
        if (!completedDate || !(expectedDate || due)) {
            return null;
        }

        const completed = new Date(`${completedDate}T00:00:00`);
        const expectedRaw = expectedDate || due;
        const expected = expectedRaw ? new Date(`${expectedRaw}T00:00:00`) : null;

        if (!expected || Number.isNaN(completed.getTime()) || Number.isNaN(expected.getTime())) {
            return null;
        }

        const diffDays = Math.round((completed.getTime() - expected.getTime()) / (1000 * 60 * 60 * 24));

        if (diffDays === 0) return "Completed on expected date";
        if (diffDays > 0) return `Completed ${diffDays} day${diffDays === 1 ? "" : "s"} after expected date`;

        const earlyDays = Math.abs(diffDays);
        return `Completed ${earlyDays} day${earlyDays === 1 ? "" : "s"} before expected date`;
    })();

    const isUrgentDue = normalizedStatus === "due" || normalizedStatus === "due_soon" || normalizedStatus === "due_today";
    const isCritical = normalizedStatus === "overdue" || normalizedStatus === "missed";

    const splitMedicalText = (value?: string) => {
        if (!value) return [];

        return value
            .split(/\n|,|;/)
            .map((item) => item.trim())
            .filter(Boolean);
    };

    const sideEffects = splitMedicalText(information?.side_effects);
    const preventionPoints = splitMedicalText(information?.prevention || information?.contraindications);
    const precautions = splitMedicalText(information?.precautions);

    return (
        <>
            <tr className={cn(
                "hover:bg-surface-container-low/30 transition-colors group",
                isCritical && "bg-red-50/40",
                isUrgentDue && "bg-amber-50/40"
            )}>
                <td className="px-8 py-6 w-full">
                    <div className="flex flex-col whitespace-nowrap">
                        <div className="flex items-center gap-2">
                            <span className="font-bold text-[#1F1E1E] text-base">
                                {name}
                            </span>

                            <button
                                type="button"
                                onClick={() => setOpen(true)}
                                className="p-1 rounded-md hover:bg-primary/10 text-primary transition-all"
                            >
                                <Eye className="w-4 h-4" />
                            </button>
                        </div>

                        <span className="text-xs font-medium text-[#4D4D4D] mt-0.5">
                            {doseNo ? `Dose ${doseNo}` : ""}{doseNo && desc ? " · " : ""}{desc}
                        </span>
                    </div>
                </td>

                <td className="px-8 py-6 w-full">
                    <div className="flex flex-col whitespace-nowrap text-sm font-semibold text-[#1F1E1E]">
                        <span>{age || "N/A"}</span>
                        {patientAge && (
                            <span className="text-xs font-medium text-[#4D4D4D]">
                                Your age: {patientAge}
                            </span>
                        )}
                    </div>
                </td>

                <td className="px-8 py-6 w-full">
                    <div className="flex flex-col whitespace-nowrap text-sm font-semibold text-[#1F1E1E]">
                        <span>{due || "N/A"}</span>
                        {assigned && (
                            <span className="text-xs font-medium text-[#4D4D4D]">
                                Assigned: {assigned}
                            </span>
                        )}
                    </div>
                </td>

                <td className="px-8 py-6 w-full">
                    <div className="flex flex-col whitespace-nowrap items-start gap-1">
                        {normalizedStatus === "completed" ? (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <CheckCircle2 className="w-3 h-3 fill-green-700 text-white" />
                                {displayStatus}
                            </span>
                        ) : normalizedStatus === "due" || normalizedStatus === "due_soon" || normalizedStatus === "due_today" ? (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-orange-50 text-orange-700 border border-orange-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <Clock className="w-3 h-3" />
                                {displayStatus}
                            </span>
                        ) : normalizedStatus === "overdue" ? (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-red-50 text-red-700 border border-red-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <AlertCircle className="w-3 h-3 fill-red-700 text-white" />
                                {displayStatus}
                            </span>
                        ) : normalizedStatus === "missed" ? (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <AlertCircle className="w-3 h-3 fill-rose-700 text-white" />
                                {displayStatus}
                            </span>
                        ) : normalizedStatus === "on_hold" ? (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <Clock className="w-3 h-3" />
                                {displayStatus}
                            </span>
                        ) : normalizedStatus === "skipped_by_doctor" ? (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 text-gray-700 border border-gray-300 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <AlertCircle className="w-3 h-3" />
                                {displayStatus}
                            </span>
                        ) : (
                            <span className="inline-flex items-center gap-2 px-3 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-full text-[10px] font-black uppercase tracking-wider">
                                <Clock className="w-3 h-3" />
                                {displayStatus}
                            </span>
                        )}
                        {normalizedStatus === "skipped_by_doctor" && skippedReason && (
                            <span className="text-[10px] text-gray-500 italic max-w-full truncate" title={skippedReason}>
                                Reason: {skippedReason}
                            </span>
                        )}
                        {normalizedStatus === "on_hold" && onHoldReason && (
                            <span className="text-[10px] text-gray-500 italic max-w-full truncate" title={onHoldReason}>
                                Reason: {onHoldReason}
                            </span>
                        )}
                        {changedDate && (
                            <span className="text-[10px] text-amber-600 font-medium">
                                Rescheduled
                            </span>
                        )}
                    </div>
                </td>
            </tr>
            {open && canUsePortal && createPortal(
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-black/20 backdrop-blur-sm"
                        onClick={() => setOpen(false)}
                    />

                    <Card className="relative w-full max-w-2xl rounded-lg overflow-hidden flex flex-col max-h-[90vh]">
                        {/* Header */}
                        <div className="flex items-start justify-between gap-3 px-5 pt-5 pb-4 border-b">
                            <div className="flex items-start gap-3">
                                <CheckCircle2 className="w-6 h-6 text-primary/60 shrink-0 mt-0.5" />
                                <div>
                                    <h3 className="font-semibold text-[#1F1E1E] text-lg leading-tight">
                                        {information?.name || name}
                                    </h3>
                                    {doseNo && (
                                        <span className="text-xs font-semibold text-primary mt-0.5 block">
                                            Dose {doseNo}
                                        </span>
                                    )}
                                </div>
                            </div>

                            <button
                                onClick={() => setOpen(false)}
                                className="p-2 rounded-md border border-light-gray bg-white hover:bg-gray-50 transition-all shrink-0"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        {/* Scrollable body */}
                        <div className="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                            {/* Description */}
                            {(information?.description || desc) && (
                                <p className="text-sm text-[#4D4D4D]">
                                    {information?.description || desc}
                                </p>
                            )}

                            {/* Scheduling details */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {age && (
                                    <div className="rounded-lg border bg-gray-50 p-3">
                                        <p className="text-[10px] font-black text-[#808080] uppercase tracking-widest">Recommended Age</p>
                                        <p className="mt-1 text-sm font-bold text-[#1F1E1E]">{age}</p>
                                    </div>
                                )}
                                {patientAge && (
                                    <div className="rounded-lg border bg-gray-50 p-3">
                                        <p className="text-[10px] font-black text-[#808080] uppercase tracking-widest">Your Age at Schedule</p>
                                        <p className="mt-1 text-sm font-bold text-[#1F1E1E]">{patientAge}</p>
                                    </div>
                                )}
                                {schedulingRule && (
                                    <div className="rounded-lg border bg-gray-50 p-3">
                                        <p className="text-[10px] font-black text-[#808080] uppercase tracking-widest">Schedule Rule</p>
                                        <p className="mt-1 text-sm font-bold text-[#1F1E1E]">{schedulingRule}</p>
                                    </div>
                                )}
                                {(gracePeriodBeforeDays || gracePeriodAfterDays) ? (
                                    <div className="rounded-lg border bg-gray-50 p-3">
                                        <p className="text-[10px] font-black text-[#808080] uppercase tracking-widest">Grace Period</p>
                                        <p className="mt-1 text-xs font-semibold text-[#4D4D4D]">
                                            -{gracePeriodBeforeDays || 0}d before / +{gracePeriodAfterDays || 0}d after
                                        </p>
                                    </div>
                                ) : null}
                            </div>

                            {/* Timeline dates */}
                            <div>
                                <p className="text-[10px] font-black text-on-surface-variant uppercase tracking-widest mb-2">
                                    Vaccination Timeline:
                                </p>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-xs font-semibold text-[#4D4D4D]">
                                    {expectedDate && (
                                        <div>
                                            <span className="text-[10px] text-gray-400 block font-normal">Expected Date</span>
                                            <span>{formatDate(expectedDate)}</span>
                                        </div>
                                    )}
                                    {assigned && (
                                        <div>
                                            <span className="text-[10px] text-gray-400 block font-normal">Assigned Date</span>
                                            <span>{formatDate(assigned)}</span>
                                        </div>
                                    )}
                                    {due && (
                                        <div>
                                            <span className="text-[10px] text-gray-400 block font-normal">Due Date</span>
                                            <span>{formatDate(due)}</span>
                                        </div>
                                    )}
                                    {normalizedStatus !== "completed" && changedDate && normalizedStatus !== "rescheduled" && (
                                        <div>
                                            <span className="text-[10px] text-amber-600 block font-normal">Rescheduled Date</span>
                                            <span className="text-amber-600">{formatDate(changedDate)}</span>
                                        </div>
                                    )}
                                    {statusDateDescriptor?.value && (
                                        <div>
                                            <span className="text-[10px] text-gray-400 block font-normal">{statusDateDescriptor.label}</span>
                                            <span className={statusDateDescriptor.colorClass}>{formatDate(statusDateDescriptor.value)}</span>
                                        </div>
                                    )}
                                </div>
                                {completionDeltaText && (
                                    <p className="mt-2 text-[11px] font-semibold text-green-700">{completionDeltaText}</p>
                                )}
                            </div>

                            {/* Safety guidance */}
                            {(sideEffects.length > 0 || preventionPoints.length > 0 || precautions.length > 0) && (
                                <div>
                                    <p className="text-[10px] font-black text-on-surface-variant uppercase tracking-widest mb-2">
                                        Dose Safety Guidance:
                                    </p>

                                    <div className="space-y-3">
                                        {sideEffects.length > 0 && (
                                            <div>
                                                <p className="text-[11px] font-bold text-[#1F1E1E] mb-1">Side Effects</p>
                                                <ul className="text-xs font-semibold text-secondary space-y-1">
                                                    {sideEffects.map((effect) => (
                                                        <li
                                                            key={`side-effect-${effect}`}
                                                            className="flex items-center gap-2 text-[#4D4D4D]"
                                                        >
                                                            <div className="w-1.5 h-1.5 rounded-full bg-primary/30 shrink-0" />
                                                            {effect}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}

                                        {preventionPoints.length > 0 && (
                                            <div>
                                                <p className="text-[11px] font-bold text-[#1F1E1E] mb-1">Prevention / Contraindications</p>
                                                <ul className="text-xs font-semibold text-secondary space-y-1">
                                                    {preventionPoints.map((item) => (
                                                        <li
                                                            key={`prevention-${item}`}
                                                            className="flex items-center gap-2 text-[#4D4D4D]"
                                                        >
                                                            <div className="w-1.5 h-1.5 rounded-full bg-amber-400/80 shrink-0" />
                                                            {item}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}

                                        {precautions.length > 0 && (
                                            <div>
                                                <p className="text-[11px] font-bold text-[#1F1E1E] mb-1">Precautions</p>
                                                <ul className="text-xs font-semibold text-secondary space-y-1">
                                                    {precautions.map((item) => (
                                                        <li
                                                            key={`precaution-${item}`}
                                                            className="flex items-center gap-2 text-[#4D4D4D]"
                                                        >
                                                            <div className="w-1.5 h-1.5 rounded-full bg-blue-400/80 shrink-0" />
                                                            {item}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Doctor notes & documents */}
                            {(doctorNotes || documents.length > 0) && (
                                <div className="border-t pt-4 space-y-3">
                                    <p className="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">
                                        Doctor Notes & Documents:
                                    </p>

                                    {doctorNotes && (
                                        <p className="rounded-md bg-surface-container-low p-3 text-xs font-medium text-[#4D4D4D]">
                                            {doctorNotes}
                                        </p>
                                    )}

                                    {documents.length > 0 && (
                                        <div className="space-y-2">
                                            {documents.map((document) => (
                                                <a
                                                    key={document.id}
                                                    href={document.document_url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="block rounded-md border border-light-gray px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/5"
                                                >
                                                    View vaccination certificate
                                                    {document.certificate_number ? ` (${document.certificate_number})` : ""}
                                                </a>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </Card>
                </div>,
                document.body
            )}

        </>
    );
}


// vacination accordian heading
interface VaccinationSetProps {
    title: string;
    subtitle?: string;
    status: "Completed" | "Upcoming" | "Pending";
    vaccinations?: ScheduleVaccination[];
    isExpanded: boolean;
    onToggle: () => void;
}

function VaccinationSet({
    title,
    subtitle,
    status,
    vaccinations = [],
    isExpanded,
    onToggle,
}: VaccinationSetProps) {
    return (
        <div className="bg-white rounded-md border-light-gray overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
            <button
                onClick={onToggle}
                className={cn(
                    "w-full flex items-center justify-between md:px-8 px-4 py-6 border-b border-outline-variant transition-colors",
                    isExpanded ? "bg-slate-100" : "bg-white"
                )}
            >
                <div className="flex items-center gap-6 text-left">
                    <div
                        className={cn(
                            "w-10 h-10 min-w-10 min-h-10 shrink-0 rounded-2xl flex items-center justify-center transition-all",
                            status === "Completed"
                                ? "bg-green-50 text-green-600 scale-110 shadow-sm"
                                : status === "Upcoming"
                                    ? "bg-primary/5 text-primary"
                                    : "bg-red-50 text-red-500"
                        )}
                    >
                        {status === "Completed" ? (
                            <CheckCircle2 className="w-6 h-6" />
                        ) : status === "Upcoming" ? (
                            <Clock className="w-6 h-6" />
                        ) : (
                            <AlertCircle className="w-6 h-6" />
                        )}
                    </div>
                    <div>
                        <div className="flex items-center gap-3">
                            <h3 className="text-lg text-[#1F1E1E] font-semibold">{title}</h3>
                            <span className="text-xs text-[#4D4D4D] bg-surface-container-low px-2 py-1 rounded-full">{(vaccinations || []).length} vaccines</span>
                        </div>
                        <p className="text-sm text-[#4D4D4D]">{subtitle}</p>
                    </div>
                </div>
                <div className="flex items-center gap-8">
                    {status === 'Upcoming' && (
                        <span className="hidden sm:inline-flex items-center gap-2 px-4 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-full text-[10px] font-black uppercase tracking-widest">
                            <AlertCircle className="w-3 h-3" />
                            Upcoming
                        </span>
                    )}
                    {isExpanded ? <ChevronUp color="#1F1E1E" size={24} /> : <ChevronDown color="#1F1E1E" size={24} />}
                </div>
            </button>

            <AnimatePresence>
                {isExpanded && (
                    <motion.div
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: 'auto', opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        className="overflow-x-auto overflow-hidden"
                    >
                        <table className="w-full text-left">
                            <thead className="bg-surface-bright border-b border-outline-variant">
                                <tr>
                                    <th className="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Vaccine Name</th>
                                    <th className="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Recommended Age</th>
                                    <th className="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Due Date</th>
                                    <th className="px-8 py-4 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-outline-variant/30">
                                {vaccinations?.map((v) => (
                                    <VaccineRow
                                        key={v.id}
                                        name={v.vaccine_name}
                                        desc={v.short_description}
                                        age={v.recommended_age}
                                        due={v.due_date}
                                        assigned={v.assigned_date}
                                        patientAge={v.patient_age_on_schedule}
                                        status={v.effective_status_label || v.status_label}
                                        effectiveStatus={(v.effective_status || v.status || "").toLowerCase()}
                                        information={v.information}
                                        doctorNotes={v.doctor_notes}
                                        documents={v.documents}
                                        skippedReason={v.skipped_reason}
                                        onHoldReason={v.on_hold_reason}
                                        changedDate={v.changed_date}
                                        expectedDate={v.expected_date}
                                        completedDate={v.completed_date}
                                        overdueDate={v.overdue_date}
                                        missedDate={v.missed_date}
                                        gracePeriodBeforeDays={v.grace_period_before_days}
                                        gracePeriodAfterDays={v.grace_period_after_days}
                                        dueAfterMonths={v.due_after_months}
                                        dueAfterDays={v.due_after_days}
                                        doseNo={v.dose_no}
                                    />
                                ))}
                            </tbody>
                        </table>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}

const VaccinationSchedule = () => {

    const [expandedSet, setExpandedSet] = useState<string | null>("1");
    const [page, setPage] = useState(1);
    const [filter, setFilter] = useState<'upcoming' | 'completed' | 'all'>('upcoming');
    const perPage = 5;

    const { data } = usePatientVaccinations(page, perPage, filter);
    const schedule: VaccinationScheduleSetType[] = data?.data?.vaccination_schedule || [];
    const pagination = data?.data?.pagination;

    const filterOptions: { value: typeof filter; label: string }[] = [
        { value: 'upcoming', label: 'Upcoming' },
        { value: 'completed', label: 'Completed' },
        { value: 'all', label: 'All' },
    ];

    const onFilterChange = (nextFilter: typeof filter) => {
        setFilter(nextFilter);
        setPage(1);
    };

    return (
        <section className="space-y-8">
            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 className="font-display text-2xl font-bold text-on-surface flex items-center gap-4">
                    <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-sm">
                        <Calendar className="w-5 h-5" />
                    </div>
                    Vaccination Schedule
                </h2>

                <div className="flex flex-wrap items-center gap-2">
                    <span className="inline-flex items-center gap-2 text-xs font-semibold text-[#4D4D4D]">
                        <Filter className="h-4 w-4" />
                        Filter
                    </span>
                    {filterOptions.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => onFilterChange(option.value)}
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

            <div className="space-y-6">
                {schedule.map((set, index) => (
                    <VaccinationSet
                        key={`${set.set_name}-${set.set_id}-${index}`}
                        title={set.set_name}
                        subtitle={set.description}
                        status={
                            set.status === "completed"
                                ? "Completed"
                                : set.status === "upcoming"
                                    ? "Upcoming"
                                    : "Pending"
                        }
                        vaccinations={set.vaccinations}
                        isExpanded={expandedSet === String(set.set_id)}
                        onToggle={() =>
                            setExpandedSet(
                                expandedSet === String(set.set_id)
                                    ? null
                                    : String(set.set_id)
                            )
                        }
                    />
                ))}
            </div>

            {pagination && pagination.last_page > 1 && (
                <CustomPagination
                    currentPage={pagination.current_page}
                    totalPages={pagination.last_page}
                    onPageChange={setPage}
                />
            )}
        </section>
    )
}

export default VaccinationSchedule
