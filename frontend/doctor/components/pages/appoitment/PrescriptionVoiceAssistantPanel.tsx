import type { VoiceTranscriptionResult } from "@/api/voice-transcription";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Loader2, Mic, X } from "lucide-react";
import DeepgramVoiceRecorder from "./DeepgramVoiceRecorder";
import type { MedicineSource, VoiceLocale } from "./prescription-dialog-types";

interface GuidedVoiceStep {
    id: number;
    title: string;
    hint: string;
}

interface PrescriptionVoiceAssistantPanelProps {
    appointmentId: string;
    deepgramEnabled: boolean;
    voiceSubMode: "deepgram" | "browser";
    setVoiceSubMode: (value: "deepgram" | "browser") => void;
    onDeepgramResult: (result: VoiceTranscriptionResult) => void;
    voiceLanguageOptions: Array<{ label: string; value: VoiceLocale }>;
    selectedSpeechLocale: VoiceLocale;
    setSelectedSpeechLocale: (value: VoiceLocale) => void;
    guidedVoiceSteps: GuidedVoiceStep[];
    guidedStep: number;
    onGuidedStepChange: (step: number) => void;
    guidedTranscripts: Record<number, string>;
    onGuidedTranscriptChange: (step: number, value: string) => void;
    speechSupported: boolean;
    isListening: boolean;
    speechError: string | null;
    isSearchingMedicine: boolean;
    selectedMedicineName: string;
    selectedMedicineSource: MedicineSource;
    showCustomConfirm: { name: string } | null;
    parseDraftPending: boolean;
    onStartListening: () => void;
    onStopListening: () => void;
    onBack: () => void;
    onNext: () => void;
    onFinish: () => void;
    onUseCustomMedicine: (value: string) => void;
    onClearSelectedMedicine: () => void;
    onCustomConfirmAccept: () => void;
    onCustomConfirmDismiss: () => void;
}

export default function PrescriptionVoiceAssistantPanel({
    appointmentId,
    deepgramEnabled,
    voiceSubMode,
    setVoiceSubMode,
    onDeepgramResult,
    voiceLanguageOptions,
    selectedSpeechLocale,
    setSelectedSpeechLocale,
    guidedVoiceSteps,
    guidedStep,
    onGuidedStepChange,
    guidedTranscripts,
    onGuidedTranscriptChange,
    speechSupported,
    isListening,
    speechError,
    isSearchingMedicine,
    selectedMedicineName,
    selectedMedicineSource,
    showCustomConfirm,
    parseDraftPending,
    onStartListening,
    onStopListening,
    onBack,
    onNext,
    onFinish,
    onUseCustomMedicine,
    onClearSelectedMedicine,
    onCustomConfirmAccept,
    onCustomConfirmDismiss,
}: PrescriptionVoiceAssistantPanelProps) {
    return (
        <div className="space-y-4">
            <div className="font-semibold text-xs text-muted-foreground border-b pb-1.5 flex items-center justify-between">
                <span>Voice Dictation Assistant</span>
                <span className="text-[10px] text-primary bg-primary/5 px-2 py-0.5 rounded border border-primary/20 font-semibold uppercase tracking-wider">
                    Voice Mode
                </span>
            </div>

            {deepgramEnabled && (
                <div className="flex gap-1 p-1 bg-muted/30 rounded-xl border border-muted/50">
                    <button
                        type="button"
                        onClick={() => setVoiceSubMode("deepgram")}
                        className={`flex-1 py-1.5 rounded-lg text-[10px] font-bold transition-all ${voiceSubMode === "deepgram" ? "bg-blue-600 text-white shadow-sm" : "text-muted-foreground hover:text-foreground"}`}
                    >
                        Deepgram Cloud AI
                    </button>
                    <button
                        type="button"
                        onClick={() => setVoiceSubMode("browser")}
                        className={`flex-1 py-1.5 rounded-lg text-[10px] font-bold transition-all ${voiceSubMode === "browser" ? "bg-background text-foreground shadow-sm border border-border" : "text-muted-foreground hover:text-foreground"}`}
                    >
                        Browser Voice
                    </button>
                </div>
            )}

            {voiceSubMode === "deepgram" && deepgramEnabled ? (
                <DeepgramVoiceRecorder appointmentId={appointmentId} onResult={onDeepgramResult} defaultLanguage="en" />
            ) : (
                <>
                    <div className="flex items-center justify-between gap-2">
                        <span className="text-xs text-muted-foreground font-medium">Select Language:</span>
                        <div className="flex items-center gap-1 rounded-lg border bg-background p-0.5">
                            {voiceLanguageOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => setSelectedSpeechLocale(option.value)}
                                    className={`rounded-md px-2.5 py-1 text-[10px] font-medium transition ${selectedSpeechLocale === option.value ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground"}`}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-4 gap-1.5">
                        {guidedVoiceSteps.map((step) => {
                            const isActive = guidedStep === step.id;
                            const isComplete = (guidedTranscripts[step.id] || "").trim() !== "";

                            return (
                                <button
                                    key={step.id}
                                    type="button"
                                    onClick={() => onGuidedStepChange(step.id)}
                                    className={`rounded-lg border p-1.5 text-left transition flex flex-col justify-between h-[52px] ${isActive ? "border-primary bg-primary/5 text-primary" : "border-border bg-background text-foreground"}`}
                                >
                                    <p className="text-[9px] font-semibold opacity-70 leading-none">Step {step.id}</p>
                                    <p className="text-[10px] font-semibold truncate leading-tight w-full">{step.title}</p>
                                    <p className={`text-[8px] leading-none ${isComplete ? "text-green-600 font-medium" : "text-muted-foreground"}`}>
                                        {isComplete ? "Captured" : "Pending"}
                                    </p>
                                </button>
                            );
                        })}
                    </div>

                    <div className="rounded-xl border bg-background p-3.5 space-y-3 shadow-inner relative">
                        <div className="flex justify-between items-start gap-2">
                            <div className="space-y-0.5">
                                <p className="text-xs font-semibold text-foreground">{guidedVoiceSteps[guidedStep - 1]?.title}</p>
                                <p className="text-[10px] text-muted-foreground leading-normal font-medium italic">{guidedVoiceSteps[guidedStep - 1]?.hint}</p>
                            </div>

                            {isListening && (
                                <div className="flex items-center gap-1 shrink-0 bg-red-50 border border-red-200 rounded-full px-2 py-0.5">
                                    <span className="h-1.5 w-1.5 rounded-full bg-red-500 animate-ping" />
                                    <span className="text-[9px] text-red-600 font-medium">Recording</span>
                                </div>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            {speechSupported ? (
                                <Button type="button" size="sm" variant={isListening ? "destructive" : "secondary"} onClick={() => (isListening ? onStopListening() : onStartListening())} className="h-8 text-xs font-medium">
                                    {isListening ? (
                                        <>
                                            <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                                            Stop Listening
                                        </>
                                    ) : (
                                        <>
                                            <Mic className="mr-1.5 h-3.5 w-3.5" />
                                            Start Speaking
                                        </>
                                    )}
                                </Button>
                            ) : (
                                <p className="text-[10px] text-red-500">Dictation not supported in browser</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">Transcription</Label>
                            <Textarea
                                value={guidedTranscripts[guidedStep] || ""}
                                onChange={(event) => onGuidedTranscriptChange(guidedStep, event.target.value)}
                                rows={3}
                                className="text-xs resize-none rounded-lg"
                                placeholder="Speak now or type custom text here..."
                                disabled={isListening}
                            />
                        </div>

                        {isSearchingMedicine && guidedStep === 1 && (
                            <div className="flex items-center gap-2 text-xs text-muted-foreground mt-2 bg-muted/20 px-3 py-1.5 rounded-lg border border-dashed animate-pulse">
                                <Loader2 className="h-3.5 w-3.5 animate-spin text-primary" />
                                Searching database...
                            </div>
                        )}

                        {selectedMedicineName && guidedStep === 1 && (
                            <div className="flex items-center justify-between rounded-lg border bg-green-50/50 border-green-200 px-3 py-1.5 mt-2 text-xs">
                                <div className="space-y-0.5">
                                    <p className="font-semibold text-green-950">Medicine Selected: {selectedMedicineName}</p>
                                    <p className="text-[10px] text-green-700">
                                        {selectedMedicineSource === "inventory"
                                            ? "Found in stock database"
                                            : selectedMedicineSource === "doctor_added"
                                                ? "Found in doctor-added database"
                                                : "Custom medicine added"}
                                    </p>
                                </div>
                                <button type="button" onClick={onClearSelectedMedicine} className="rounded-md p-1 hover:bg-green-100 text-green-700 transition-colors">
                                    <X className="h-3.5 w-3.5" />
                                </button>
                            </div>
                        )}

                        {showCustomConfirm && guidedStep === 1 && (
                            <div className="rounded-xl border border-amber-200 bg-amber-50/70 p-3.5 space-y-3 mt-2 text-xs text-amber-950 shadow-sm">
                                <p className="font-bold flex items-center gap-1.5 text-amber-800">
                                    <svg className="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Medicine Not Found
                                </p>
                                <p className="text-[11px] leading-relaxed">
                                    &ldquo;{showCustomConfirm.name}&rdquo; was not found in the database. Do you want to add it as a custom medicine?
                                </p>
                                <div className="flex gap-2">
                                    <Button type="button" size="sm" onClick={onCustomConfirmAccept} className="h-7 text-[10px] px-3 font-semibold bg-primary hover:bg-primary/90 text-white rounded-lg shadow-sm">
                                        Yes, Add Custom
                                    </Button>
                                    <Button type="button" size="sm" variant="outline" onClick={onCustomConfirmDismiss} className="h-7 text-[10px] px-3 font-semibold border-amber-300 text-amber-900 hover:bg-amber-100/50 rounded-lg">
                                        No, Record Again
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center justify-between gap-3 bg-background p-1.5">
                        <Button type="button" size="sm" variant="outline" onClick={onBack} disabled={guidedStep === 1 || isListening} className="h-8 text-xs font-medium">
                            Back
                        </Button>

                        {guidedStep < guidedVoiceSteps.length ? (
                            <Button type="button" size="sm" onClick={onNext} disabled={isListening} className="h-8 text-xs font-medium">
                                Next Step
                            </Button>
                        ) : (
                            <Button type="button" size="sm" onClick={onFinish} disabled={parseDraftPending || !Object.values(guidedTranscripts).some((val) => Boolean(val.trim()))} className="h-8 text-xs font-medium">
                                {parseDraftPending ? (
                                    <>
                                        <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                                        Parsing...
                                    </>
                                ) : (
                                    "Prefill Form Fields"
                                )}
                            </Button>
                        )}
                    </div>

                    {speechError && (
                        <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">{speechError}</div>
                    )}
                </>
            )}
        </div>
    );
}
