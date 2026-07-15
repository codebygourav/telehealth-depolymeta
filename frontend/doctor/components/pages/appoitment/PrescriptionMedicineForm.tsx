import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Search, Stethoscope, X, Calendar, Clock, BookOpen, AlertCircle } from "lucide-react";
import type { MedicineItem, MedicineSource, MedicineStatus, PrescriptionForm } from "./prescription-dialog-types";

type TimingFieldName = "timing_morning" | "timing_afternoon" | "timing_evening" | "timing_night";

type OptionItem = { label: string; value: string };

interface PrescriptionMedicineFormProps {
    title: string;
    subtitle?: string;
    editingIndex: number | null;
    selectedMedicineName: string;
    selectedMedicineSource: MedicineSource;
    searchQuery: string;
    setSearchQuery: (value: string) => void;
    medicineList: MedicineItem[];
    isSearchingMedicine: boolean;
    medicineStatus: MedicineStatus | null;
    errors: Record<string, { message?: string } | undefined>;
    medicationType: string | undefined;
    strength: string | undefined;
    dosage: string | undefined;
    frequency: string | undefined;
    meal: PrescriptionForm["meal"] | undefined;
    applicationArea: string | undefined;
    remarks: string | undefined;
    followUpNote: string | undefined;
    timingMorning: boolean | undefined;
    timingAfternoon: boolean | undefined;
    timingEvening: boolean | undefined;
    timingNight: boolean | undefined;
    startDate: string;
    endDate: string;
    instructions: string | undefined;
    medicationTypeOptions: OptionItem[];
    strengthOptions: OptionItem[];
    frequencyOptions: OptionItem[];
    mealOptions: OptionItem[];
    dosageOptions: OptionItem[];
    applicationAreaOptions: OptionItem[];
    durationOptions: OptionItem[];
    fieldRules: string[];
    visibleFields?: Array<"medicine_name" | "medication_type" | "strength" | "dosage" | "frequency" | "meal" | "application_area" | "remarks" | "follow_up_note" | "timing" | "duration">;
    mode?: "full" | "compact";
    onSelectMedicine: (medicine: MedicineItem) => void;
    onUseCustomMedicine: (value: string) => void;
    onClearSelectedMedicine: () => void;
    onMedicationTypeChange: (value: string) => void;
    onStrengthChange: (value: string) => void;
    onDosageChange: (value: string) => void;
    onFrequencyChange: (value: string) => void;
    onMealChange: (value: string) => void;
    onApplicationAreaChange: (value: string) => void;
    onDurationPresetChange: (value: string) => void;
    onRemarksChange: (value: string) => void;
    onFollowUpNoteChange: (value: string) => void;
    onTimingChange: (name: TimingFieldName, value: boolean) => void;
    onStartDateChange: (value: string) => void;
    onEndDateChange: (value: string) => void;
    onInstructionsChange: (value: string) => void;
    onSubmit: () => void;
    onCancel?: () => void;
    submitLabel: string;
    cancelLabel?: string;
    fullWidthButton?: boolean;
}

export default function PrescriptionMedicineForm({
    title,
    subtitle,
    editingIndex,
    selectedMedicineName,
    selectedMedicineSource,
    searchQuery,
    setSearchQuery,
    medicineList,
    isSearchingMedicine,
    medicineStatus,
    errors,
    medicationType,
    strength,
    dosage,
    frequency,
    meal,
    applicationArea,
    remarks,
    followUpNote,
    timingMorning,
    timingAfternoon,
    timingEvening,
    timingNight,
    startDate,
    endDate,
    instructions,
    medicationTypeOptions,
    strengthOptions,
    frequencyOptions,
    mealOptions,
    dosageOptions,
    applicationAreaOptions,
    durationOptions,
    fieldRules,
    visibleFields = ["medicine_name", "medication_type", "strength", "dosage", "frequency", "meal", "application_area", "remarks", "follow_up_note", "timing", "duration"],
    mode = "full",
    onSelectMedicine,
    onUseCustomMedicine,
    onClearSelectedMedicine,
    onMedicationTypeChange,
    onStrengthChange,
    onDosageChange,
    onFrequencyChange,
    onMealChange,
    onApplicationAreaChange,
    onDurationPresetChange,
    onRemarksChange,
    onFollowUpNoteChange,
    onTimingChange,
    onStartDateChange,
    onEndDateChange,
    onInstructionsChange,
    onSubmit,
    onCancel,
    submitLabel,
    cancelLabel = "Cancel",
    fullWidthButton = false,
}: PrescriptionMedicineFormProps) {
    const showAdvancedFields = mode === "full";
    
    const shouldShowField = (field: typeof visibleFields[number]) =>
        visibleFields.includes(field) && (fieldRules.length === 0 || fieldRules.includes(field));

    return (
        <div className="overflow-hidden rounded-[28px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_rgba(15,23,42,0.08)]">
            <div className="border-b border-slate-200 bg-linear-to-r from-sky-50 via-white to-emerald-50 px-4 py-4 sm:px-5 flex items-center justify-between gap-3">
                <div className="space-y-1">
                    <p className="text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-700">Prescription Builder</p>
                    <span className="font-bold text-slate-900 text-base sm:text-lg">{title}</span>
                </div>
                {editingIndex !== null && (
                    <span className="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200 font-semibold uppercase tracking-wider">
                        Editing Item #{editingIndex + 1}
                    </span>
                )}
            </div>

            <div className="space-y-5 p-4 sm:p-5">
            {subtitle && <p className="text-[11px] text-slate-500 -mt-2 leading-relaxed">{subtitle}</p>}

            {/* Medicine Search Section */}
            {shouldShowField("medicine_name") && (
                <div className="space-y-1.5 relative rounded-2xl border border-slate-200 bg-slate-50/70 p-3">
                    <Label className="text-xs font-semibold text-slate-700">Medicine Name *</Label>
                    {selectedMedicineName ? (
                        <div className="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5 hover:border-slate-300 transition shadow-sm">
                            <div className="space-y-0.5">
                                <p className="text-xs font-bold text-slate-900">{selectedMedicineName}</p>
                                <p className="text-[10px] text-slate-500 font-medium flex items-center gap-1">
                                    <span className={`h-1.5 w-1.5 rounded-full ${
                                        selectedMedicineSource === "inventory" ? "bg-green-500" : selectedMedicineSource === "doctor_added" ? "bg-amber-500" : "bg-blue-500"
                                    }`} />
                                    {selectedMedicineSource === "inventory"
                                        ? "Admin Medicine Master"
                                        : selectedMedicineSource === "doctor_added"
                                            ? "Doctor-added Database"
                                            : "Custom Medicine Name"}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={onClearSelectedMedicine}
                                className="rounded-xl p-1.5 hover:bg-slate-100 text-slate-500 hover:text-slate-900 transition-colors"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                    ) : (
                        <div className="space-y-1">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    placeholder="Search database or type custom medicine name..."
                                    value={searchQuery}
                                    onChange={(event) => setSearchQuery(event.target.value)}
                                    className="pl-9 h-11 text-xs rounded-2xl border-slate-200 bg-white shadow-sm"
                                />
                            </div>

                            {searchQuery.trim() !== "" && (
                                <div className="max-h-52 overflow-y-auto rounded-2xl border border-slate-200 bg-white text-xs shadow-2xl absolute z-20 w-full left-0 mt-2 ring-1 ring-black/5 animate-in fade-in slide-in-from-top-1 duration-150">
                                    <button
                                        key="use-custom-btn"
                                        type="button"
                                        onClick={() => onUseCustomMedicine(searchQuery)}
                                        className="flex w-full items-center justify-between border-b border-slate-100 px-3.5 py-2.5 text-left hover:bg-sky-50 font-semibold text-sky-700"
                                    >
                                        <span>Use custom: &quot;{searchQuery.trim()}&quot;</span>
                                        <Stethoscope className="h-4 w-4 text-primary" />
                                    </button>
                                    {isSearchingMedicine ? (
                                        <div className="px-3.5 py-3 text-slate-500 text-[11px] flex items-center gap-2">
                                            <Loader2 className="h-3 w-3 animate-spin text-sky-600" />
                                            Searching database...
                                        </div>
                                    ) : medicineList.length > 0 ? (
                                        medicineList.map((medicine) => (
                                            <button
                                                key={`${medicine.source}-${medicine.id}`}
                                                type="button"
                                                onClick={() => onSelectMedicine(medicine)}
                                                className="flex w-full items-center justify-between px-3.5 py-2.5 text-left hover:bg-slate-50 transition-colors"
                                            >
                                                <span className="font-medium text-slate-900">{medicine.name}</span>
                                                <span className="text-[9px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wider">
                                                    {medicine.source === "inventory" ? "stock" : "custom"}
                                                </span>
                                            </button>
                                        ))
                                    ) : (
                                        <div className="px-3.5 py-3 text-slate-500 text-[11px]">No medicines found. Use &quot;Use custom&quot; above.</div>
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                    {errors.medicine_name && (
                        <p className="text-[11px] text-red-500 font-medium">{errors.medicine_name.message}</p>
                    )}
                </div>
            )}

            {medicineStatus && <MedicineStatusCard {...medicineStatus} />}

            {/* Core Medicine Properties - Grid is fully responsive */}
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                {shouldShowField("medication_type") && (
                    <div className="space-y-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <Label className="text-xs font-semibold text-slate-700">Medication Type *</Label>
                        <Select value={medicationType} onValueChange={onMedicationTypeChange}>
                            <SelectTrigger className="h-10 text-xs rounded-2xl bg-white border-slate-200">
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                {medicationTypeOptions.map((item) => (
                                    <SelectItem key={item.value} value={item.value} className="text-xs">
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.medication_type && (
                            <p className="text-[11px] text-red-500 font-medium">{errors.medication_type.message}</p>
                        )}
                    </div>
                )}

                {shouldShowField("strength") && (
                    <div className="space-y-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <Label className="text-xs font-semibold text-slate-700">Strength</Label>
                        {strengthOptions.length > 0 ? (
                            <Select value={strength || ""} onValueChange={onStrengthChange}>
                                <SelectTrigger className="h-10 text-xs rounded-2xl bg-white border-slate-200">
                                    <SelectValue placeholder="Select strength" />
                                </SelectTrigger>
                                <SelectContent>
                                    {strengthOptions.map((item) => (
                                        <SelectItem key={item.value} value={item.value} className="text-xs">
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ) : (
                            <Input value={strength || ""} onChange={(event) => onStrengthChange(event.target.value)} placeholder="e.g. 500 mg" className="h-10 text-xs rounded-2xl border-slate-200 bg-white" />
                        )}
                    </div>
                )}

                {shouldShowField("dosage") && (
                    <div className="space-y-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <Label className="text-xs font-semibold text-slate-700">Dosage *</Label>
                        <Select value={dosage} onValueChange={onDosageChange}>
                            <SelectTrigger className="h-10 text-xs rounded-2xl bg-white border-slate-200">
                                <SelectValue placeholder="Select dosage" />
                            </SelectTrigger>
                            <SelectContent>
                                {dosageOptions.map((item) => (
                                    <SelectItem key={item.value} value={item.value} className="text-xs">
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.dosage && (
                            <p className="text-[11px] text-red-500 font-medium">{errors.dosage.message}</p>
                        )}
                    </div>
                )}
            </div>

            {/* Frequency & Meal - Grid is responsive */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {shouldShowField("frequency") && (
                    <div className="space-y-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <Label className="text-xs font-semibold text-slate-700">Frequency *</Label>
                        <Select value={frequency} onValueChange={onFrequencyChange}>
                            <SelectTrigger className="h-10 text-xs rounded-2xl bg-white border-slate-200">
                                <SelectValue placeholder="Select frequency" />
                            </SelectTrigger>
                            <SelectContent>
                                {frequencyOptions.map((item) => (
                                    <SelectItem key={item.value} value={item.value} className="text-xs">
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.frequency && (
                            <p className="text-[11px] text-red-500 font-medium">{errors.frequency.message}</p>
                        )}
                    </div>
                )}

                {shouldShowField("meal") && (
                    <div className="space-y-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                        <Label className="text-xs font-semibold text-slate-700">Meal Relation *</Label>
                        <Select value={meal || ""} onValueChange={onMealChange}>
                            <SelectTrigger className="h-10 text-xs rounded-2xl bg-white border-slate-200">
                                <SelectValue placeholder="Select meal timing" />
                            </SelectTrigger>
                            <SelectContent>
                                {mealOptions.map((item) => (
                                    <SelectItem key={item.value} value={item.value} className="text-xs">
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.meal && (
                            <p className="text-[11px] text-red-500 font-medium">{errors.meal.message}</p>
                        )}
                    </div>
                )}
            </div>

            {showAdvancedFields && (
                <div className="space-y-5 pt-4 border-t border-dashed border-slate-200/80">
                    {/* Timings Picker (if configured in rules) */}
                    {shouldShowField("timing") && (
                        <div className="space-y-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-3">
                            <Label className="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                <Clock className="h-3.5 w-3.5 text-muted-foreground" />
                                Timings
                            </Label>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                {[
                                    { label: "Morning", name: "timing_morning" as const, val: timingMorning },
                                    { label: "Afternoon", name: "timing_afternoon" as const, val: timingAfternoon },
                                    { label: "Evening", name: "timing_evening" as const, val: timingEvening },
                                    { label: "Night", name: "timing_night" as const, val: timingNight },
                                ].map((item) => (
                                    <button
                                        key={item.label}
                                        type="button"
                                        onClick={() => onTimingChange(item.name, !item.val)}
                                        className={`py-2 px-3 text-[11px] font-semibold border rounded-2xl text-center transition-all ${item.val
                                            ? "bg-slate-900 text-white border-slate-900 shadow"
                                            : "bg-white text-slate-500 border-slate-200 hover:bg-slate-50"
                                            }`}
                                    >
                                        {item.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Application Area & Duration Presets */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {shouldShowField("application_area") && applicationAreaOptions.length > 0 && (
                            <div className="space-y-1">
                                <Label className="text-xs font-semibold text-slate-700">Application Area</Label>
                                <Select value={applicationArea || ""} onValueChange={onApplicationAreaChange}>
                                    <SelectTrigger className="h-10 text-xs rounded-2xl bg-white border-slate-200">
                                        <SelectValue placeholder="Select area" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {applicationAreaOptions.map((item) => (
                                            <SelectItem key={item.value} value={item.value} className="text-xs">
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {shouldShowField("duration") && durationOptions.length > 0 && (
                            <div className="space-y-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                                <Label className="text-xs font-semibold text-slate-700">Duration Option</Label>
                                <Select value="" onValueChange={onDurationPresetChange}>
                                    <SelectTrigger className="h-10 text-xs rounded-2xl bg-white border-slate-200">
                                        <SelectValue placeholder="Use admin duration" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {durationOptions.map((item) => (
                                            <SelectItem key={item.value} value={item.value} className="text-xs">
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>

                    {/* Duration Range (Calendar input) */}
                    {shouldShowField("duration") && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="space-y-1">
                                <Label className="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <Calendar className="h-3.5 w-3.5 text-muted-foreground" />
                                    Start Date
                                </Label>
                                <Input type="date" min={new Date().toISOString().split("T")[0]} value={startDate} onChange={(event) => onStartDateChange(event.target.value)} className="h-10 text-xs rounded-2xl border-slate-200 bg-white" />
                            </div>
                            <div className="space-y-1">
                                <Label className="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <Calendar className="h-3.5 w-3.5 text-muted-foreground" />
                                    End Date
                                </Label>
                                <Input type="date" min={startDate || new Date().toISOString().split("T")[0]} value={endDate} onChange={(event) => onEndDateChange(event.target.value)} className="h-10 text-xs rounded-2xl border-slate-200 bg-white" />
                            </div>
                        </div>
                    )}

                    {/* Standard Instructions */}
                    <div className="space-y-1.5">
                        <Label className="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                            <BookOpen className="h-3.5 w-3.5 text-muted-foreground" />
                            Instructions / Notes
                        </Label>
                        <Textarea rows={2} value={instructions || ""} onChange={(event) => onInstructionsChange(event.target.value)} placeholder="e.g. Take after food with warm water" className="text-xs resize-none rounded-2xl border-slate-200 bg-white" />
                    </div>

                    {/* Remarks (Conditional) */}
                    {shouldShowField("remarks") && (
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold text-slate-700">Additional Remarks</Label>
                            <Textarea rows={2} value={remarks || ""} onChange={(event) => onRemarksChange(event.target.value)} placeholder="Any extra medicine-specific remarks" className="text-xs resize-none rounded-2xl border-slate-200 bg-white" />
                        </div>
                    )}

                    {/* Medicine specific Follow-up Note (Conditional) */}
                    {shouldShowField("follow_up_note") && (
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold text-slate-700">Patient Follow-up Note for PDF</Label>
                            <Textarea rows={2} value={followUpNote || ""} onChange={(event) => onFollowUpNoteChange(event.target.value)} placeholder="e.g. Review after 3 days if fever persists" className="text-xs resize-none rounded-2xl border-slate-200 bg-white" />
                        </div>
                    )}
                </div>
            )}

            {/* Bottom Submit Buttons */}
            <div className="flex flex-col sm:flex-row gap-2.5 pt-4 border-t border-slate-200 mt-4">
                {onCancel && (
                    <Button type="button" variant="outline" onClick={onCancel} className="h-11 text-xs rounded-2xl order-2 sm:order-1 sm:flex-1 border-slate-200 bg-white">
                        {cancelLabel}
                    </Button>
                )}
                <Button type="button" onClick={onSubmit} className={`${fullWidthButton ? "w-full" : "flex-1"} h-11 text-xs rounded-2xl font-semibold shadow-sm order-1 sm:order-2 bg-slate-900 hover:bg-slate-800 text-white`}>
                    {submitLabel}
                </Button>
            </div>
            </div>
        </div>
    );
}

function MedicineStatusCard({
    tone,
    title,
    description,
}: MedicineStatus) {
    const cardStyles = {
        green: "border-green-200 bg-green-50/50 text-green-950 shadow-sm",
        amber: "border-amber-200 bg-amber-50/50 text-amber-950 shadow-sm",
        blue: "border-blue-200 bg-blue-50/50 text-blue-950 shadow-sm",
    }[tone];

    return (
        <div className={`rounded-xl border p-3.5 space-y-1 flex items-start gap-3 ${cardStyles}`}>
            <AlertCircle className={`h-4 w-4 shrink-0 mt-0.5 ${
                tone === "green" ? "text-green-600" : tone === "amber" ? "text-amber-600" : "text-blue-600"
            }`} />
            <div className="space-y-0.5">
                <p className="text-xs font-bold leading-normal">{title}</p>
                <p className="text-[10px] sm:text-xs leading-normal opacity-90">{description}</p>
            </div>
        </div>
    );
}
