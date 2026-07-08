import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Search, Stethoscope, X } from "lucide-react";
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
    dosage: string | undefined;
    frequency: string | undefined;
    meal: PrescriptionForm["meal"] | undefined;
    timingMorning: boolean | undefined;
    timingAfternoon: boolean | undefined;
    timingEvening: boolean | undefined;
    timingNight: boolean | undefined;
    startDate: string;
    endDate: string;
    instructions: string | undefined;
    medicationTypeOptions: OptionItem[];
    frequencyOptions: OptionItem[];
    mealOptions: OptionItem[];
    dosageOptions: OptionItem[];
    visibleFields?: Array<"medicine_name" | "medication_type" | "dosage" | "frequency" | "meal">;
    mode?: "full" | "compact";
    onSelectMedicine: (medicine: MedicineItem) => void;
    onUseCustomMedicine: (value: string) => void;
    onClearSelectedMedicine: () => void;
    onMedicationTypeChange: (value: string) => void;
    onDosageChange: (value: string) => void;
    onFrequencyChange: (value: string) => void;
    onMealChange: (value: string) => void;
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
    dosage,
    frequency,
    meal,
    timingMorning,
    timingAfternoon,
    timingEvening,
    timingNight,
    startDate,
    endDate,
    instructions,
    medicationTypeOptions,
    frequencyOptions,
    mealOptions,
    dosageOptions,
    visibleFields = ["medicine_name", "medication_type", "dosage", "frequency", "meal"],
    mode = "full",
    onSelectMedicine,
    onUseCustomMedicine,
    onClearSelectedMedicine,
    onMedicationTypeChange,
    onDosageChange,
    onFrequencyChange,
    onMealChange,
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
    const shouldShowField = (field: "medicine_name" | "medication_type" | "dosage" | "frequency" | "meal") =>
        visibleFields.includes(field);

    return (
        <div className="space-y-4">
            <div className="font-semibold text-xs text-muted-foreground border-b pb-1.5 flex items-center justify-between">
                <span>{title}</span>
                {editingIndex !== null && (
                    <span className="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 font-semibold uppercase tracking-wider">
                        Editing Item #{editingIndex + 1}
                    </span>
                )}
            </div>

            {subtitle && <p className="text-[11px] text-muted-foreground">{subtitle}</p>}

            {shouldShowField("medicine_name") && (
                <div className="space-y-1 relative">
                    <Label className="text-xs font-semibold">Medicine Name *</Label>
                    {selectedMedicineName ? (
                        <div className="flex items-center justify-between rounded-lg border bg-muted/20 px-3 py-1.5">
                            <div className="space-y-0.5">
                                <p className="text-xs font-bold text-foreground">{selectedMedicineName}</p>
                                <p className="text-[10px] text-muted-foreground font-medium">
                                    {selectedMedicineSource === "inventory"
                                        ? "Main Medicine Inventory"
                                        : selectedMedicineSource === "doctor_added"
                                            ? "Doctor-added Database"
                                            : "Custom Medicine Name"}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={onClearSelectedMedicine}
                                className="rounded-md p-1 hover:bg-muted text-muted-foreground transition-colors"
                            >
                                <X className="h-3.5 w-3.5" />
                            </button>
                        </div>
                    ) : (
                        <div className="space-y-1">
                            <div className="relative">
                                <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Search database or type custom medicine name..."
                                    value={searchQuery}
                                    onChange={(event) => setSearchQuery(event.target.value)}
                                    className="pl-8 h-8.5 text-xs rounded-lg"
                                />
                            </div>

                            {searchQuery.trim() !== "" && (
                                <div className="max-h-48 overflow-y-auto rounded-lg border bg-background text-xs shadow-lg absolute z-20 w-full left-0 mt-1">
                                    <button
                                        key="use-custom-btn"
                                        type="button"
                                        onClick={() => onUseCustomMedicine(searchQuery)}
                                        className="flex w-full items-center justify-between border-b px-3 py-2 text-left hover:bg-muted/40 font-semibold text-primary"
                                    >
                                        <span>Use custom: &quot;{searchQuery.trim()}&quot;</span>
                                        <Stethoscope className="h-3.5 w-3.5" />
                                    </button>
                                    {isSearchingMedicine ? (
                                        <div className="px-3 py-2.5 text-muted-foreground text-[11px]">Searching database...</div>
                                    ) : medicineList.length > 0 ? (
                                        medicineList.map((medicine) => (
                                            <button
                                                key={`${medicine.source}-${medicine.id}`}
                                                type="button"
                                                onClick={() => onSelectMedicine(medicine)}
                                                className="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-muted/40 transition-colors"
                                            >
                                                <span>{medicine.name}</span>
                                                <span className="text-[10px] text-muted-foreground bg-muted px-1 py-0.5 rounded">
                                                    {medicine.source === "inventory" ? "stock" : "custom"}
                                                </span>
                                            </button>
                                        ))
                                    ) : (
                                        <div className="px-3 py-2.5 text-muted-foreground text-[11px]">No medicines found. Use &quot;Use custom&quot; above.</div>
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

            <div className="grid grid-cols-2 gap-3">
                {shouldShowField("medication_type") && (
                    <div className="space-y-1">
                        <Label className="text-xs font-semibold">Medication Type *</Label>
                        <Select value={medicationType} onValueChange={onMedicationTypeChange}>
                            <SelectTrigger className="h-8.5 text-xs rounded-lg">
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

                {shouldShowField("dosage") && (
                    <div className="space-y-1">
                        <Label className="text-xs font-semibold">Dosage *</Label>
                        <Select value={dosage} onValueChange={onDosageChange}>
                            <SelectTrigger className="h-8.5 text-xs rounded-lg">
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

            <div className="grid grid-cols-2 gap-3">
                {shouldShowField("frequency") && (
                    <div className="space-y-1">
                        <Label className="text-xs font-semibold">Frequency *</Label>
                        <Select value={frequency} onValueChange={onFrequencyChange}>
                            <SelectTrigger className="h-8.5 text-xs rounded-lg">
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
                    <div className="space-y-1">
                        <Label className="text-xs font-semibold">Meal Relation *</Label>
                        <Select value={meal || ""} onValueChange={onMealChange}>
                            <SelectTrigger className="h-8.5 text-xs rounded-lg">
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
                <>
                    <div className="space-y-1.5">
                        <Label className="text-xs font-semibold">Timings</Label>
                        <div className="grid grid-cols-4 gap-2">
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
                                    className={`py-1.5 px-2 text-[10px] sm:text-[11px] font-semibold border rounded-lg text-center transition-all ${item.val
                                            ? "bg-primary text-primary-foreground border-primary shadow-sm"
                                            : "bg-background text-muted-foreground border-border hover:bg-muted/50"
                                        }`}
                                >
                                    {item.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">Start Date</Label>
                            <Input type="date" min={new Date().toISOString().split("T")[0]} value={startDate} onChange={(event) => onStartDateChange(event.target.value)} className="h-8.5 text-xs rounded-lg" />
                        </div>
                        <div className="space-y-1">
                            <Label className="text-xs font-semibold">End Date</Label>
                            <Input type="date" min={startDate || new Date().toISOString().split("T")[0]} value={endDate} onChange={(event) => onEndDateChange(event.target.value)} className="h-8.5 text-xs rounded-lg" />
                        </div>
                    </div>

                    <div className="space-y-1">
                        <Label className="text-xs font-semibold">Instructions / Notes</Label>
                        <Textarea rows={2} value={instructions || ""} onChange={(event) => onInstructionsChange(event.target.value)} placeholder="e.g. Take after food with warm water" className="text-xs resize-none rounded-lg" />
                    </div>
                </>
            )}

            <div className="flex gap-2 pt-2 border-t mt-3">
                {onCancel && (
                    <Button type="button" variant="outline" onClick={onCancel} className="h-9 text-xs rounded-lg">
                        {cancelLabel}
                    </Button>
                )}
                <Button type="button" onClick={onSubmit} className={`${fullWidthButton ? "w-full" : "flex-1"} h-9 text-xs rounded-lg font-semibold shadow-sm`}>
                    {submitLabel}
                </Button>
            </div>
        </div>
    );
}

function MedicineStatusCard({
    tone,
    title,
    description,
}: MedicineStatus) {
    const toneClasses = {
        green: "border-green-200 bg-green-50 text-green-800",
        amber: "border-amber-200 bg-amber-50 text-amber-800",
        blue: "border-blue-200 bg-blue-50 text-blue-800",
    }[tone];

    return (
        <div className={`rounded-2xl border p-4 ${toneClasses}`}>
            <p className="text-sm font-semibold">{title}</p>
            <p className="mt-1 text-xs sm:text-sm">{description}</p>
        </div>
    );
}
