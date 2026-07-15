"use client";

import { useQueryClient } from "@tanstack/react-query";
import { BrainCircuit, Loader2, Plus, Save, Trash2 } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";

import { updateDoctorProfile } from "@/api/profile";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { doctorProfileKeys } from "@/queries/useProfile";
import type { AiTrainingProfile } from "@/types/profile";

interface AiTrainingSectionProps {
    aiTraining: AiTrainingProfile | null | undefined;
    userId: string | undefined;
}

type PronunciationRule = {
    doctor_says: string;
    ai_converts_to: string;
};

type SpeechWordCorrection = {
    heard_word: string;
    corrected_word: string;
};

type MedicineShortcut = {
    medicine: string;
    shortcut: string;
    priority: number;
};

const createEmptyPronunciation = (): PronunciationRule => ({
    doctor_says: "",
    ai_converts_to: "",
});

const createEmptyMedicineShortcut = (): MedicineShortcut => ({
    medicine: "",
    shortcut: "",
    priority: 3,
});

const createEmptySpeechWordCorrection = (): SpeechWordCorrection => ({
    heard_word: "",
    corrected_word: "",
});

export default function AiTrainingSection({ aiTraining, userId }: AiTrainingSectionProps) {
    const queryClient = useQueryClient();

    const [isSaving, setIsSaving] = useState(false);
    const [pronunciationDictionary, setPronunciationDictionary] = useState<PronunciationRule[]>([]);
    const [medicineShortcuts, setMedicineShortcuts] = useState<MedicineShortcut[]>([]);
    const [speechWordCorrections, setSpeechWordCorrections] = useState<SpeechWordCorrection[]>([]);
    const [commonDiagnoses, setCommonDiagnoses] = useState<string[]>([]);
    const [instructions, setInstructions] = useState<string[]>([]);
    const [procedures, setProcedures] = useState<string[]>([]);

    const safeProfile = useMemo(() => ({
        pronunciation_dictionary: aiTraining?.pronunciation_dictionary ?? [],
        speech_word_corrections: aiTraining?.speech_word_corrections ?? [],
        medicine_shortcuts: aiTraining?.medicine_shortcuts ?? [],
        common_diagnoses: aiTraining?.common_diagnoses ?? [],
        frequently_used_instructions: aiTraining?.frequently_used_instructions ?? [],
        procedures_investigations: aiTraining?.procedures_investigations ?? [],
    }), [aiTraining]);

    useEffect(() => {
        setPronunciationDictionary(
            safeProfile.pronunciation_dictionary.map((item) => ({
                doctor_says: item.doctor_says || "",
                ai_converts_to: item.ai_converts_to || "",
            })),
        );

        setMedicineShortcuts(
            safeProfile.medicine_shortcuts.map((item) => ({
                medicine: item.medicine || "",
                shortcut: item.shortcut || "",
                priority: Number(item.priority || 3),
            })),
        );

        setSpeechWordCorrections(
            safeProfile.speech_word_corrections.map((item) => ({
                heard_word: item.heard_word || "",
                corrected_word: item.corrected_word || "",
            })),
        );

        setCommonDiagnoses(safeProfile.common_diagnoses.map((item) => item || ""));
        setInstructions(safeProfile.frequently_used_instructions.map((item) => item || ""));
        setProcedures(safeProfile.procedures_investigations.map((item) => item || ""));
    }, [safeProfile]);

    const handleSave = async () => {
        if (!userId) {
            toast.error("User session not found.");
            return;
        }

        const payload = {
            pronunciation_dictionary: pronunciationDictionary
                .map((item) => ({
                    doctor_says: item.doctor_says.trim(),
                    ai_converts_to: item.ai_converts_to.trim(),
                }))
                .filter((item) => item.doctor_says && item.ai_converts_to),
            speech_word_corrections: speechWordCorrections
                .map((item) => ({
                    heard_word: item.heard_word.trim(),
                    corrected_word: item.corrected_word.trim(),
                }))
                .filter((item) => item.heard_word && item.corrected_word),
            medicine_shortcuts: medicineShortcuts
                .map((item) => ({
                    medicine: item.medicine.trim(),
                    shortcut: item.shortcut.trim(),
                    priority: Math.max(1, Math.min(5, Number(item.priority || 3))),
                }))
                .filter((item) => item.medicine && item.shortcut),
            common_diagnoses: commonDiagnoses.map((item) => item.trim()).filter(Boolean),
            frequently_used_instructions: instructions.map((item) => item.trim()).filter(Boolean),
            procedures_investigations: procedures.map((item) => item.trim()).filter(Boolean),
        };

        setIsSaving(true);
        try {
            await updateDoctorProfile(userId, "ai_training", {
                ai_training_profile: payload,
            });

            toast.success("AI training profile saved successfully.");
            queryClient.invalidateQueries({ queryKey: doctorProfileKeys.all });
        } catch (error) {
            console.error("Failed to save AI training profile", error);
            toast.error("Failed to save AI training profile.");
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-[#1F1E1E] font-semibold text-lg mb-1.5 flex items-center gap-2">
                    <BrainCircuit className="h-5 w-5 text-primary" /> AI Speech Training
                </h2>
                <p className="text-[#4D4D4D] text-sm">
                    Train pronunciation mappings, medicine shortcuts, and reusable instructions to improve dictation speed and consistency.
                </p>
            </div>

            <Card className="border-border overflow-hidden bg-linear-to-br from-background via-background to-muted/20 shadow-md">
                <CardContent className="p-6 space-y-7">
                    <section className="space-y-3 pb-6 border-b border-border">
                        <div className="flex items-center justify-between">
                            <div>
                                <Label className="text-sm font-bold text-foreground">Pronunciation Dictionary</Label>
                                <p className="text-xs text-muted-foreground mt-0.5">Map how you speak to the exact clinical term the AI should use.</p>
                            </div>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={() => setPronunciationDictionary((prev) => [...prev, createEmptyPronunciation()])}
                            >
                                <Plus className="h-4 w-4 mr-1" /> Add
                            </Button>
                        </div>

                        <div className="space-y-2">
                            {pronunciationDictionary.length === 0 && (
                                <p className="text-xs text-muted-foreground">No pronunciation rules added yet.</p>
                            )}
                            {pronunciationDictionary.map((row, index) => (
                                <div key={`pronunciation-${index}`} className="grid grid-cols-12 gap-2">
                                    <Input
                                        className="col-span-5"
                                        placeholder="Doctor says"
                                        value={row.doctor_says}
                                        onChange={(e) => {
                                            const next = [...pronunciationDictionary];
                                            next[index] = { ...next[index], doctor_says: e.target.value };
                                            setPronunciationDictionary(next);
                                        }}
                                    />
                                    <Input
                                        className="col-span-6"
                                        placeholder="AI converts to"
                                        value={row.ai_converts_to}
                                        onChange={(e) => {
                                            const next = [...pronunciationDictionary];
                                            next[index] = { ...next[index], ai_converts_to: e.target.value };
                                            setPronunciationDictionary(next);
                                        }}
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="col-span-1"
                                        onClick={() => setPronunciationDictionary((prev) => prev.filter((_, i) => i !== index))}
                                    >
                                        <Trash2 className="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="space-y-3 pb-6 border-b border-border">
                        <div className="flex items-center justify-between">
                            <div>
                                <Label className="text-sm font-bold text-foreground">Speech Word Corrections</Label>
                                <p className="text-xs text-muted-foreground mt-0.5">Correct common misheard words. Example: meal becomes mail.</p>
                            </div>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={() => setSpeechWordCorrections((prev) => [...prev, createEmptySpeechWordCorrection()])}
                            >
                                <Plus className="h-4 w-4 mr-1" /> Add
                            </Button>
                        </div>

                        <div className="space-y-2">
                            {speechWordCorrections.length === 0 && (
                                <p className="text-xs text-muted-foreground">No speech correction rules added yet.</p>
                            )}
                            {speechWordCorrections.map((row, index) => (
                                <div key={`speech-word-${index}`} className="grid grid-cols-12 gap-2">
                                    <Input
                                        className="col-span-5"
                                        placeholder="AI heard (Example: mail)"
                                        value={row.heard_word}
                                        onChange={(e) => {
                                            const next = [...speechWordCorrections];
                                            next[index] = { ...next[index], heard_word: e.target.value };
                                            setSpeechWordCorrections(next);
                                        }}
                                    />
                                    <Input
                                        className="col-span-6"
                                        placeholder="Should be (Example: meal)"
                                        value={row.corrected_word}
                                        onChange={(e) => {
                                            const next = [...speechWordCorrections];
                                            next[index] = { ...next[index], corrected_word: e.target.value };
                                            setSpeechWordCorrections(next);
                                        }}
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="col-span-1"
                                        onClick={() => setSpeechWordCorrections((prev) => prev.filter((_, i) => i !== index))}
                                    >
                                        <Trash2 className="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="space-y-3 pb-6 border-b border-border">
                        <div className="flex items-center justify-between">
                            <div>
                                <Label className="text-sm font-bold text-foreground">Medicine Shortcuts</Label>
                                <p className="text-xs text-muted-foreground mt-0.5">Link your quick spoken codes to the full medicine name.</p>
                            </div>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={() => setMedicineShortcuts((prev) => [...prev, createEmptyMedicineShortcut()])}
                            >
                                <Plus className="h-4 w-4 mr-1" /> Add
                            </Button>
                        </div>

                        <div className="space-y-2">
                            {medicineShortcuts.length === 0 && (
                                <p className="text-xs text-muted-foreground">No medicine shortcuts added yet.</p>
                            )}
                            {medicineShortcuts.map((row, index) => (
                                <div key={`shortcut-${index}`} className="grid grid-cols-12 gap-2">
                                    <Input
                                        className="col-span-5"
                                        placeholder="Medicine name"
                                        value={row.medicine}
                                        onChange={(e) => {
                                            const next = [...medicineShortcuts];
                                            next[index] = { ...next[index], medicine: e.target.value };
                                            setMedicineShortcuts(next);
                                        }}
                                    />
                                    <Input
                                        className="col-span-4"
                                        placeholder="Shortcut"
                                        value={row.shortcut}
                                        onChange={(e) => {
                                            const next = [...medicineShortcuts];
                                            next[index] = { ...next[index], shortcut: e.target.value };
                                            setMedicineShortcuts(next);
                                        }}
                                    />
                                    <Input
                                        className="col-span-2"
                                        type="number"
                                        min={1}
                                        max={5}
                                        placeholder="1-5"
                                        value={row.priority}
                                        onChange={(e) => {
                                            const next = [...medicineShortcuts];
                                            next[index] = { ...next[index], priority: Number(e.target.value || 3) };
                                            setMedicineShortcuts(next);
                                        }}
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="col-span-1"
                                        onClick={() => setMedicineShortcuts((prev) => prev.filter((_, i) => i !== index))}
                                    >
                                        <Trash2 className="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </section>

                    <SimpleListEditor
                        title="Common Diagnoses"
                        subtitle="Save frequent diagnoses to insert quickly while dictating or typing findings."
                        value={commonDiagnoses}
                        onChange={setCommonDiagnoses}
                        placeholder="Diagnosis"
                    />

                    <SimpleListEditor
                        title="Frequently Used Instructions"
                        subtitle="Store reusable patient instruction lines for one-click insertion in notes."
                        value={instructions}
                        onChange={setInstructions}
                        placeholder="Instruction"
                    />

                    <SimpleListEditor
                        title="Procedures & Investigations"
                        subtitle="Keep common test and procedure names handy for faster report recommendations."
                        value={procedures}
                        onChange={setProcedures}
                        placeholder="Procedure / Investigation"
                    />

                    <div className="flex justify-end">
                        <Button onClick={handleSave} disabled={isSaving} className="px-6 h-10 font-medium flex items-center gap-2 shadow-md">
                            {isSaving ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" /> Saving...
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4" /> Save AI Training
                                </>
                            )}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

function SimpleListEditor({
    title,
    subtitle,
    value,
    onChange,
    placeholder,
}: {
    title: string;
    subtitle?: string;
    value: string[];
    onChange: (next: string[]) => void;
    placeholder: string;
}) {
    return (
        <section className="space-y-3 pb-6 border-b border-border last:border-b-0 last:pb-0">
            <div className="flex items-center justify-between">
                <div>
                    <Label className="text-sm font-bold text-foreground">{title}</Label>
                    {subtitle && <p className="text-xs text-muted-foreground mt-0.5">{subtitle}</p>}
                </div>
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() => onChange([...value, ""])}
                >
                    <Plus className="h-4 w-4 mr-1" /> Add
                </Button>
            </div>

            <div className="space-y-2">
                {value.length === 0 && (
                    <p className="text-xs text-muted-foreground">No entries added yet.</p>
                )}
                {value.map((item, index) => (
                    <div key={`${title}-${index}`} className="grid grid-cols-12 gap-2">
                        <Input
                            className="col-span-11"
                            placeholder={placeholder}
                            value={item}
                            onChange={(e) => {
                                const next = [...value];
                                next[index] = e.target.value;
                                onChange(next);
                            }}
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            className="col-span-1"
                            onClick={() => onChange(value.filter((_, i) => i !== index))}
                        >
                            <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                ))}
            </div>
        </section>
    );
}
