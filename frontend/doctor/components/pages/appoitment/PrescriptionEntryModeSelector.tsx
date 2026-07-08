import { Mic, Stethoscope } from "lucide-react";

interface PrescriptionEntryModeSelectorProps {
    dictationEnabled: boolean;
    onSelectVoice: () => void;
    onSelectManual: () => void;
}

export default function PrescriptionEntryModeSelector({
    dictationEnabled,
    onSelectVoice,
    onSelectManual,
}: PrescriptionEntryModeSelectorProps) {
    return (
        <div className="flex flex-col items-center justify-center py-10 max-w-2xl mx-auto space-y-6">
            <div className="text-center space-y-1.5">
                <h3 className="text-base font-bold text-foreground">Select Entry Method</h3>
                <p className="text-xs text-muted-foreground">
                    Choose Voice Prescription to dictate or Manual Entry to type the details.
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 w-full">
                <button
                    type="button"
                    onClick={onSelectVoice}
                    disabled={!dictationEnabled}
                    className={`rounded-2xl border p-5 text-left transition-all ${dictationEnabled
                            ? "border-border bg-background hover:border-primary hover:bg-primary/5 shadow-sm hover:shadow"
                            : "cursor-not-allowed border-dashed bg-muted/30 opacity-60"
                        }`}
                >
                    <div className="flex items-start gap-4">
                        <div className="rounded-xl bg-primary/10 p-3 text-primary shrink-0">
                            <Mic className="h-6 w-6" />
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm font-bold text-foreground">Voice Prescription</p>
                            <p className="text-xs text-muted-foreground leading-normal">
                                Dictate medicine details. AI assistant will parse and construct the prescription.
                            </p>
                            {!dictationEnabled && (
                                <p className="text-[10px] text-amber-700 font-semibold bg-amber-50 px-2 py-0.5 rounded mt-2 border border-amber-200 inline-block">
                                    Voice prescription is unavailable for this account.
                                </p>
                            )}
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    onClick={onSelectManual}
                    className="rounded-2xl border border-border bg-background p-5 text-left transition-all hover:border-primary hover:bg-primary/5 shadow-sm hover:shadow"
                >
                    <div className="flex items-start gap-4">
                        <div className="rounded-xl bg-primary/10 p-3 text-primary shrink-0">
                            <Stethoscope className="h-6 w-6" />
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm font-bold text-foreground">Manual Prescription</p>
                            <p className="text-xs text-muted-foreground leading-normal">
                                Type medicine details manually. Fill in fields in a single compact screen.
                            </p>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    );
}
