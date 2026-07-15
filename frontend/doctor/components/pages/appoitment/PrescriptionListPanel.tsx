import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Loader2, Stethoscope, X, Mic, ClipboardList, FileText } from "lucide-react";
import { useState, useRef } from "react";
import { useDoctorProfile } from "@/queries/useProfile";
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

    // New doctor general notes props
    generalNotes: string;
    onGeneralNotesChange: (value: string) => void;

    // Added findings & reports props
    findingsText?: string;
    nextVisitDate?: string;
    includeReports?: boolean;
    recommendedTests?: string;
    reportFiles?: File[];
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
    generalNotes,
    onGeneralNotesChange,
    findingsText = "",
    nextVisitDate = "",
    includeReports = false,
    recommendedTests = "",
    reportFiles = [],
}: PrescriptionListPanelProps) {
    const { data: profileResponse } = useDoctorProfile();
    const doctorVoiceLocale = profileResponse?.data?.voice_settings?.speech_locale;
    const doctorAiTraining = profileResponse?.data?.ai_training;
    const instructionSuggestions = (doctorAiTraining?.frequently_used_instructions ?? [])
        .map((item) => item.trim())
        .filter(Boolean)
        .slice(0, 10);
    const pronunciationDictionary = doctorAiTraining?.pronunciation_dictionary ?? [];
    const medicineShortcuts = doctorAiTraining?.medicine_shortcuts ?? [];

    const [isListeningNotes, setIsListeningNotes] = useState(false);
    const recognitionRef = useRef<any>(null);

    const toggleListeningNotes = () => {
        if (isListeningNotes) {
            recognitionRef.current?.stop();
            setIsListeningNotes(false);
            return;
        }

        const SpeechRecognitionApi = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
        if (!SpeechRecognitionApi) {
            alert("This browser does not support voice dictation.");
            return;
        }

        const rec = new SpeechRecognitionApi();
        rec.continuous = true;
        rec.interimResults = true;
        rec.lang = doctorVoiceLocale || "en-IN";

        const initialText = generalNotes.trim();

        rec.onresult = (event: any) => {
            let finalTrans = "";
            let interimTrans = "";
            for (let i = 0; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTrans += transcript + " ";
                } else {
                    interimTrans += transcript;
                }
            }
            const spoken = (finalTrans + interimTrans).trim();
            const cleanedSpoken = applyDoctorTrainingVocabulary(
                cleanDuplicateWords(spoken),
                pronunciationDictionary,
                medicineShortcuts,
            );
            if (cleanedSpoken) {
                onGeneralNotesChange(initialText ? `${initialText} ${cleanedSpoken}` : cleanedSpoken);
            } else if (!spoken && initialText) {
                onGeneralNotesChange(initialText);
            }
        };

        rec.onerror = () => {
            setIsListeningNotes(false);
        };

        rec.onend = () => {
            setIsListeningNotes(false);
        };

        recognitionRef.current = rec;
        setIsListeningNotes(true);
        rec.start();
    };

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
                    <p className="text-[10px] text-muted-foreground/80 mt-1 max-w-45 leading-relaxed">
                        Fill and add details using the form on the left to build the prescription.
                    </p>
                </div>
            ) : (
                <div className="max-h-95 overflow-y-auto space-y-3 pr-1 min-h-0">
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
                                        <div className="col-span-2 italic bg-muted/40 p-1.5 rounded-lg border text-[10px] leading-relaxed wrap-break-word font-medium">
                                            &ldquo;{med.instructions}&rdquo;
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Live Previews */}
            {(findingsText.trim() || nextVisitDate) && (
                <div className="p-3 border border-primary/20 rounded-xl bg-primary/5 text-xs space-y-2 animate-in fade-in duration-200">
                    <div className="font-bold text-primary flex items-center gap-1.5">
                        <ClipboardList className="h-3.5 w-3.5" />
                        <span>Findings & Notes Preview</span>
                    </div>
                    <div className="space-y-1 text-[11px] text-muted-foreground">
                        {findingsText.trim() && (
                            <p className="line-clamp-3 italic">
                                &ldquo;{findingsText.trim()}&rdquo;
                            </p>
                        )}
                        {nextVisitDate && (
                            <p>
                                <span className="font-semibold text-foreground">Follow-up Visit:</span> {nextVisitDate}
                            </p>
                        )}
                    </div>
                </div>
            )}

            {includeReports && (recommendedTests.trim() || reportFiles.length > 0) && (
                <div className="p-3 border border-indigo-200 rounded-xl bg-indigo-50/50 text-xs space-y-2 animate-in fade-in duration-200">
                    <div className="font-bold text-indigo-700 flex items-center gap-1.5">
                        <FileText className="h-3.5 w-3.5" />
                        <span>Diagnostics Preview</span>
                    </div>
                    <div className="space-y-1.5 text-[11px] text-muted-foreground">
                        {recommendedTests.trim() && (
                            <div>
                                <span className="font-semibold text-foreground">Suggested Tests:</span>
                                <p className="italic line-clamp-2">&ldquo;{recommendedTests.trim()}&rdquo;</p>
                            </div>
                        )}
                        {reportFiles.length > 0 && (
                            <p className="font-semibold text-indigo-700">
                                📎 {reportFiles.length} file{reportFiles.length > 1 ? "s" : ""} to upload
                            </p>
                        )}
                    </div>
                </div>
            )}

            <div className="pt-4 border-t space-y-4 bg-background">
                {/* Global Doctor Notes field */}
                <div className="space-y-1.5">
                    <div className="flex items-center justify-between">
                        <Label className="text-xs font-semibold">Doctor Notes / Patient Instructions</Label>
                        <button
                            type="button"
                            onClick={toggleListeningNotes}
                            className={`p-1.5 rounded-full border transition-all ${isListeningNotes
                                ? "bg-red-500 text-white border-red-500 animate-pulse shadow-sm"
                                : "bg-blue-50 hover:bg-blue-100/80 text-blue-600 border-blue-200 shadow-sm"
                                }`}
                            title="Dictate general notes"
                        >
                            <Mic className="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <Textarea
                        rows={3}
                        value={generalNotes}
                        onChange={(e) => onGeneralNotesChange(e.target.value)}
                        placeholder="Write or dictate general notes, diagnosis, or patient instructions here. This will be printed on the prescription PDF."
                        className="text-xs rounded-lg resize-none bg-background"
                    />
                    {instructionSuggestions.length > 0 && (
                        <div className="flex flex-wrap gap-1.5 pt-1">
                            {instructionSuggestions.map((item) => (
                                <button
                                    key={item}
                                    type="button"
                                    onClick={() => {
                                        const existing = generalNotes.trim();
                                        const next = existing
                                            ? `${existing}${existing.endsWith(".") ? "" : "."} ${item}`
                                            : item;
                                        onGeneralNotesChange(next.trim());
                                    }}
                                    className="text-[10px] px-2 py-0.5 rounded-full border border-green-200 bg-green-50 text-green-700 hover:bg-green-100 transition"
                                >
                                    {item}
                                </button>
                            ))}
                        </div>
                    )}
                </div>

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

                <Button 
                    type="button" 
                    onClick={onFinalSubmit} 
                    disabled={addPrescriptionPending || (addedMedicines.length === 0 && !findingsText.trim() && !nextVisitDate && !recommendedTests.trim() && reportFiles.length === 0)} 
                    className="w-full h-10 text-xs sm:text-sm font-semibold rounded-lg shadow-sm"
                >
                    {addPrescriptionPending ? (
                        <>
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            Saving consultation & prescription...
                        </>
                    ) : (
                        `Save & Submit (${addedMedicines.length} meds)`
                    )}
                </Button>
            </div>
        </div>
    );
}

function cleanDuplicateWords(text: string): string {
    if (!text) return "";

    let cleaned = text.replace(/\s+/g, " ").trim();

    let prev = "";
    while (cleaned !== prev) {
        prev = cleaned;
        cleaned = cleaned.replace(/(.{5,})\s*\1/gi, "$1");
    }

    const words = cleaned.split(/\s+/);
    const result: string[] = [];
    for (let i = 0; i < words.length; i++) {
        const word = words[i];
        if (i > 0 && word.toLowerCase() === words[i - 1].toLowerCase()) {
            continue;
        }
        result.push(word);
    }

    return result.join(" ").trim();
}

function applyDoctorTrainingVocabulary(
    input: string,
    pronunciationDictionary: Array<{ doctor_says?: string; ai_converts_to?: string }>,
    medicineShortcuts: Array<{ shortcut?: string; medicine?: string }>,
): string {
    let text = input;

    for (const row of pronunciationDictionary || []) {
        const from = String(row?.doctor_says || "").trim();
        const to = String(row?.ai_converts_to || "").trim();
        if (!from || !to) continue;

        const escaped = from.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        text = text.replace(new RegExp(`\\b${escaped}\\b`, "gi"), to);
    }

    for (const row of medicineShortcuts || []) {
        const from = String(row?.shortcut || "").trim();
        const to = String(row?.medicine || "").trim();
        if (!from || !to) continue;

        const escaped = from.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        text = text.replace(new RegExp(`\\b${escaped}\\b`, "gi"), to);
    }

    return cleanDuplicateWords(text);
}
