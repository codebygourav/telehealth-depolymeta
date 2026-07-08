import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Loader2, Stethoscope, X } from "lucide-react";
import type { AddedMedicine, PrescriptionForm } from "./prescription-dialog-types";

interface PrescriptionListPanelProps {
    addedMedicines: AddedMedicine[];
    onEditMedicine: (index: number) => void;
    onDeleteMedicine: (index: number) => void;
    stampPreference: PrescriptionForm["stamp_preference"] | undefined;
    stampOptions: Array<{ label: string; value: string }>;
    onStampChange: (value: string) => void;
    onFinalSubmit: () => void;
    addPrescriptionPending: boolean;
    errors: Record<string, { message?: string } | undefined>;
    frequencyOptions: Array<{ label: string; value: string }>;
    mealOptions: Array<{ label: string; value: string }>;
    mobileTab: "form" | "list";
}

export default function PrescriptionListPanel({
    addedMedicines,
    onEditMedicine,
    onDeleteMedicine,
    stampPreference,
    stampOptions,
    onStampChange,
    onFinalSubmit,
    addPrescriptionPending,
    errors,
    frequencyOptions,
    mealOptions,
    mobileTab,
}: PrescriptionListPanelProps) {
    return (
        <div className={`md:col-span-5 bg-background border rounded-2xl p-4 sm:p-5 shadow-sm space-y-4 self-start ${mobileTab === "list" ? "block" : "hidden md:block"}`}>
            <div className="font-semibold text-xs text-muted-foreground border-b pb-2 flex items-center justify-between">
                <span>Prescription Items</span>
                <span className="text-[10px] font-bold bg-primary/10 text-primary px-2.5 py-0.5 rounded-full border border-primary/20 shadow-sm">
                    {addedMedicines.length} {addedMedicines.length === 1 ? "Item" : "Items"}
                </span>
            </div>

            {addedMedicines.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-14 text-center border-2 border-dashed rounded-xl p-4 bg-muted/5">
                    <Stethoscope className="h-10 w-10 text-muted-foreground/50 mb-2 stroke-[1.2]" />
                    <p className="text-xs font-semibold text-muted-foreground">No medicines added</p>
                    <p className="text-[10px] text-muted-foreground/80 mt-1 max-w-[180px] leading-relaxed">
                        Fill and add details using the form on the left to build the prescription.
                    </p>
                </div>
            ) : (
                <div className="max-h-[380px] overflow-y-auto space-y-3 pr-1 min-h-0">
                    {addedMedicines.map((med, index) => {
                        const timingsList = [
                            med.timing_morning ? "Morning" : null,
                            med.timing_afternoon ? "Afternoon" : null,
                            med.timing_evening ? "Evening" : null,
                            med.timing_night ? "Night" : null,
                        ].filter(Boolean);

                        return (
                            <div key={index} className="p-3 border rounded-xl bg-background hover:shadow-md hover:border-muted-foreground/30 transition-all text-xs space-y-2 relative group shadow-sm">
                                <div className="flex justify-between items-start gap-2">
                                    <div className="space-y-0.5">
                                        <div className="flex items-center gap-1.5 flex-wrap">
                                            <span className="font-bold text-foreground text-sm leading-tight">{med.medicine_name}</span>
                                            <span className="text-[9px] bg-muted px-1.5 py-0.5 rounded text-muted-foreground font-semibold uppercase tracking-wider">
                                                {med.medication_type}
                                            </span>
                                        </div>
                                        <p className="text-[10px] sm:text-[11px] text-muted-foreground">
                                            {med.dosage} • {frequencyOptions.find((f) => f.value === med.frequency)?.label || med.frequency}
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-1 opacity-80 group-hover:opacity-100 transition-opacity">
                                        <button type="button" onClick={() => onEditMedicine(index)} className="p-1 hover:bg-muted rounded text-primary transition-colors" title="Edit medicine">
                                            <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button type="button" onClick={() => onDeleteMedicine(index)} className="p-1 hover:bg-destructive/10 rounded text-destructive transition-colors" title="Remove medicine">
                                            <X className="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-y-1.5 gap-x-2 text-[10px] text-muted-foreground pt-2 border-t mt-1 leading-normal">
                                    <div>
                                        <span className="font-semibold text-foreground">Timing:</span>{" "}
                                        {timingsList.length > 0 ? timingsList.join(", ") : "As needed"}
                                    </div>
                                    <div>
                                        <span className="font-semibold text-foreground">Meal:</span>{" "}
                                        {mealOptions.find((m) => m.value === med.meal)?.label || med.meal?.replace("_", " ")}
                                    </div>
                                    {med.start_date && (
                                        <div className="col-span-2">
                                            <span className="font-semibold text-foreground">Duration:</span>{" "}
                                            {med.start_date} {med.end_date ? `to ${med.end_date}` : "(Ongoing)"}
                                        </div>
                                    )}
                                    {med.instructions && (
                                        <div className="col-span-2 italic bg-muted/40 p-1.5 rounded-lg border text-[10px] leading-relaxed break-words font-medium">
                                            &ldquo;{med.instructions}&rdquo;
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            <div className="pt-4 border-t space-y-3 bg-background">
                <div className="space-y-1">
                    <Label className="text-xs font-semibold">Stamp Preference *</Label>
                    <Select value={stampPreference} onValueChange={onStampChange}>
                        <SelectTrigger className="h-8.5 text-xs rounded-lg">
                            <SelectValue placeholder="Select stamp preference" />
                        </SelectTrigger>
                        <SelectContent>
                            {stampOptions.map((item) => (
                                <SelectItem key={item.value} value={item.value} className="text-xs">
                                    {item.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.stamp_preference && <p className="text-[11px] text-red-500 font-medium">{errors.stamp_preference.message}</p>}
                </div>

                <Button type="button" onClick={onFinalSubmit} disabled={addPrescriptionPending || addedMedicines.length === 0} className="w-full h-10 text-xs sm:text-sm font-semibold rounded-lg shadow-sm">
                    {addPrescriptionPending ? (
                        <>
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            Saving Prescription...
                        </>
                    ) : (
                        `Save & Submit Prescription (${addedMedicines.length})`
                    )}
                </Button>
            </div>
        </div>
    );
}
