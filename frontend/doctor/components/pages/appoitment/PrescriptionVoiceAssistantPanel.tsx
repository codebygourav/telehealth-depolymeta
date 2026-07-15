import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Loader2, Mic, X, Check } from "lucide-react";
import type { MedicineSource, VoiceLocale } from "./prescription-dialog-types";

interface GuidedVoiceStep {
  id: number;
  title: string;
  hint: string;
}

interface OptionItem {
  label: string;
  value: string;
}

interface PrescriptionVoiceAssistantPanelProps {
  browserVoiceEnabled: boolean;
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
  isFinishing: boolean;
  onStartListening: () => void;
  onStopListening: () => void;
  onBack: () => void;
  onNext: () => void;
  onFinish: () => void;
  onClearSelectedMedicine: () => void;
  onCustomConfirmAccept: () => void;
  onCustomConfirmDismiss: () => void;

  // New suggestion props
  medicationTypeOptions?: OptionItem[];
  dosageOptions?: OptionItem[];
  strengthOptions?: OptionItem[];
  frequencyOptions?: OptionItem[];
  mealOptions?: OptionItem[];
  durationOptions?: OptionItem[];
  applicationAreaOptions?: OptionItem[];
  commonInstructionSuggestions?: string[];
}

export default function PrescriptionVoiceAssistantPanel({
  browserVoiceEnabled,
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
  isFinishing,
  onStartListening,
  onStopListening,
  onBack,
  onNext,
  onFinish,
  onClearSelectedMedicine,
  onCustomConfirmAccept,
  onCustomConfirmDismiss,
  medicationTypeOptions = [],
  dosageOptions = [],
  strengthOptions = [],
  frequencyOptions = [],
  mealOptions = [],
  durationOptions = [],
  applicationAreaOptions = [],
  commonInstructionSuggestions = [],
}: PrescriptionVoiceAssistantPanelProps) {
  const browserSpeechAvailable = browserVoiceEnabled && speechSupported;
  const controlsBusy = isListening || isFinishing;

  const handleSuggestionClick = (value: string) => {
    const currentText = guidedTranscripts[guidedStep] || "";
    const trimmedVal = value.trim();

    // Split the current text by comma to check for existing items
    const existingItems = currentText.split(',').map(item => item.trim().toLowerCase());

    if (existingItems.includes(trimmedVal.toLowerCase())) {
      return; // Already exists, do not add it again
    }

    const newText = currentText ? `${currentText}, ${trimmedVal}` : trimmedVal;
    onGuidedTranscriptChange(guidedStep, newText);
  };

  const getSuggestionsForStep = (stepId: number) => {
    if (!selectedMedicineName && stepId !== 1) return null;

    switch (stepId) {
      case 2:
        return (
          <div className="space-y-3 p-3 bg-secondary/40 rounded-lg border border-border/60">
            <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
              Dictation Hints (Speak or click to select):
            </p>
            <div className="space-y-2">
              {medicationTypeOptions.length > 0 && (
                <div>
                  <span className="text-[9px] font-semibold text-muted-foreground block mb-1">Types:</span>
                  <div className="flex flex-wrap gap-1.5">
                    {medicationTypeOptions.map((opt) => (
                      <button
                        key={opt.value}
                        type="button"
                        onClick={() => handleSuggestionClick(opt.label)}
                        className="text-[10px] bg-background hover:bg-primary/10 hover:text-primary hover:border-primary/40 border rounded-md px-2 py-0.5 font-medium transition"
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>
                </div>
              )}
              {dosageOptions.length > 0 && (
                <div>
                  <span className="text-[9px] font-semibold text-muted-foreground block mb-1">Dosages:</span>
                  <div className="flex flex-wrap gap-1.5">
                    {dosageOptions.map((opt) => (
                      <button
                        key={opt.value}
                        type="button"
                        onClick={() => handleSuggestionClick(opt.label)}
                        className="text-[10px] bg-background hover:bg-primary/10 hover:text-primary hover:border-primary/40 border rounded-md px-2 py-0.5 font-medium transition"
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>
                </div>
              )}
              {strengthOptions.length > 0 && (
                <div>
                  <span className="text-[9px] font-semibold text-muted-foreground block mb-1">Strengths:</span>
                  <div className="flex flex-wrap gap-1.5">
                    {strengthOptions.map((opt) => (
                      <button
                        key={opt.value}
                        type="button"
                        onClick={() => handleSuggestionClick(opt.label)}
                        className="text-[10px] bg-background hover:bg-primary/10 hover:text-primary hover:border-primary/40 border rounded-md px-2 py-0.5 font-medium transition"
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>
        );
      case 3:
        return (
          <div className="space-y-3 p-3 bg-secondary/40 rounded-lg border border-border/60">
            <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
              Dictation Hints (Speak or click to select):
            </p>
            <div className="space-y-2">
              {frequencyOptions.length > 0 && (
                <div>
                  <span className="text-[9px] font-semibold text-muted-foreground block mb-1">Frequencies:</span>
                  <div className="flex flex-wrap gap-1.5">
                    {frequencyOptions.map((opt) => (
                      <button
                        key={opt.value}
                        type="button"
                        onClick={() => handleSuggestionClick(opt.label)}
                        className="text-[10px] bg-background hover:bg-primary/10 hover:text-primary hover:border-primary/40 border rounded-md px-2 py-0.5 font-medium transition"
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>
                </div>
              )}
              <div>
                <span className="text-[9px] font-semibold text-muted-foreground block mb-1">Timings:</span>
                <div className="flex flex-wrap gap-1.5">
                  {["Morning", "Afternoon", "Evening", "Night"].map((t) => (
                    <button
                      key={t}
                      type="button"
                      onClick={() => handleSuggestionClick(t)}
                      className="text-[10px] bg-background hover:bg-primary/10 hover:text-primary hover:border-primary/40 border rounded-md px-2 py-0.5 font-medium transition"
                    >
                      {t}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        );
      case 4:
        return (
          <div className="space-y-3 p-3 bg-secondary/40 rounded-lg border border-border/60">
            <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
              Dictation Hints (Speak or click to select):
            </p>
            <div className="space-y-2">
              {mealOptions.length > 0 && (
                <div>
                  <span className="text-[9px] font-semibold text-muted-foreground block mb-1">Meal Relation:</span>
                  <div className="flex flex-wrap gap-1.5">
                    {mealOptions.map((opt) => (
                      <button
                        key={opt.value}
                        type="button"
                        onClick={() => handleSuggestionClick(opt.label)}
                        className="text-[10px] bg-background hover:bg-primary/10 hover:text-primary hover:border-primary/40 border rounded-md px-2 py-0.5 font-medium transition"
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>
                </div>
              )}
              {durationOptions.length > 0 && (
                <div>
                  <span className="text-[9px] font-semibold text-muted-foreground block mb-1">Durations:</span>
                  <div className="flex flex-wrap gap-1.5">
                    {durationOptions.map((opt) => (
                      <button
                        key={opt.value}
                        type="button"
                        onClick={() => handleSuggestionClick(opt.label)}
                        className="text-[10px] bg-background hover:bg-primary/10 hover:text-primary hover:border-primary/40 border rounded-md px-2 py-0.5 font-medium transition"
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>
                </div>
              )}
              {applicationAreaOptions.length > 0 && (
                <div>
                  <span className="text-[9px] font-semibold text-muted-foreground block mb-1">Application Areas:</span>
                  <div className="flex flex-wrap gap-1.5">
                    {applicationAreaOptions.map((opt) => (
                      <button
                        key={opt.value}
                        type="button"
                        onClick={() => handleSuggestionClick(opt.label)}
                        className="text-[10px] bg-background hover:bg-primary/10 hover:text-primary hover:border-primary/40 border rounded-md px-2 py-0.5 font-medium transition"
                      >
                        {opt.label}
                      </button>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>
        );
      case 5:
        const instructionSuggestions = commonInstructionSuggestions.length > 0
          ? commonInstructionSuggestions
          : [
            "Drink plenty of water",
            "Avoid cold food and drinks",
            "Take complete bed rest",
            "Review after 3 days",
            "If pain persists, visit clinic",
          ];

        return (
          <div className="space-y-3 p-3 bg-secondary/40 rounded-lg border border-border/60">
            <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
              Dictation Hints (Speak or click to select):
            </p>
            <div className="space-y-2">
              <div>
                <span className="text-[9px] font-semibold text-muted-foreground block mb-1">Common Instructions:</span>
                <div className="flex flex-wrap gap-1.5">
                  {instructionSuggestions.map((note) => (
                    <button
                      key={note}
                      type="button"
                      onClick={() => handleSuggestionClick(note)}
                      className="text-[10px] bg-background hover:bg-primary/10 hover:text-primary hover:border-primary/40 border rounded-md px-2 py-0.5 font-medium transition text-left"
                    >
                      {note}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        );
      default:
        return null;
    }
  };

  return (
    <div className="space-y-4">



      {/* Vertical Steps List */}
      <div className="flex flex-col gap-3">
        {guidedVoiceSteps.map((step) => {
          const isActive = guidedStep === step.id;
          const isComplete = (guidedTranscripts[step.id] || "").trim() !== "";

          return (
            <div
              key={step.id}
              className={`transition-all duration-200 ${isActive
                ? "border border-primary/20 bg-primary/5 rounded-xl p-3 sm:p-4 shadow-sm"
                : "border-b border-border/40 hover:bg-muted/10 p-1"
                }`}
            >
              <button
                type="button"
                onClick={() => onGuidedStepChange(step.id)}
                disabled={controlsBusy}
                className="w-full text-left flex items-center justify-between"
              >
                <div className="flex items-center gap-3">
                  <div
                    className={`h-6 w-6 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 transition-all ${isActive
                      ? "bg-primary text-primary-foreground shadow"
                      : isComplete
                        ? "bg-green-100 text-green-700 border border-green-200"
                        : "bg-muted text-muted-foreground border border-border"
                      }`}
                  >
                    {isComplete && !isActive ? (
                      <Check className="h-3.5 w-3.5 stroke-[3]" />
                    ) : (
                      step.id
                    )}
                  </div>
                  <div className="space-y-0.5">
                    <p className={`text-xs font-semibold ${isActive ? "text-primary" : "text-foreground"}`}>
                      {step.title}
                    </p>
                    <p className="text-[10px] text-muted-foreground leading-normal font-medium">
                      {step.hint}
                    </p>
                  </div>
                </div>
                <div className="shrink-0 text-right">
                  <span
                    className={`text-[9px] px-2 py-0.5 rounded-full font-semibold uppercase tracking-wider ${isComplete
                      ? "bg-green-100/70 text-green-700 border border-green-200/50"
                      : "bg-muted text-muted-foreground"
                      }`}
                  >
                    {isComplete ? "Captured" : "Pending"}
                  </span>
                </div>
              </button>

              {/* Inline Content for the Active Step */}
              {isActive && (
                <div className="mt-3.5 pt-3.5 border-t border-dashed border-primary/20 space-y-3">
                  {/* Suggestion Chips */}
                  {getSuggestionsForStep(step.id)}

                  {/* Dictation Controls */}
                  <div className="flex items-center justify-between gap-2">
                    <div className="flex flex-wrap items-center gap-2">
                      {browserSpeechAvailable ? (
                        <Button
                          type="button"
                          size="sm"
                          variant={isListening ? "destructive" : "secondary"}
                          onClick={() =>
                            isListening ? onStopListening() : onStartListening()
                          }
                          className="h-8 text-xs font-medium"
                        >
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
                        <p className="text-[10px] text-red-500 font-medium">
                          Browser voice is disabled or unsupported in this browser.
                        </p>
                      )}
                    </div>

                    {isListening && (
                      <div className="flex items-center gap-1 shrink-0 bg-red-50 border border-red-200 rounded-full px-2.5 py-0.5 animate-pulse">
                        <span className="h-1.5 w-1.5 rounded-full bg-red-500 animate-ping" />
                        <span className="text-[9px] text-red-600 font-bold uppercase tracking-wider">
                          Recording
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Transcription text area */}
                  <div className="space-y-1">
                    <Label className="text-[9px] font-semibold text-muted-foreground uppercase tracking-wider">
                      Transcription
                    </Label>
                    <Textarea
                      value={guidedTranscripts[step.id] || ""}
                      onChange={(event) =>
                        onGuidedTranscriptChange(step.id, event.target.value)
                      }
                      rows={2}
                      className="text-xs resize-none rounded-lg bg-background"
                      placeholder="Speak or click suggestions to input..."
                      disabled={controlsBusy}
                    />
                  </div>

                  {/* Search indicators & status confirmations (Step 1 specific) */}
                  {isSearchingMedicine && step.id === 1 && (
                    <div className="flex items-center gap-2 text-xs text-muted-foreground mt-2 bg-muted/20 px-3 py-1.5 rounded-lg border border-dashed animate-pulse">
                      <Loader2 className="h-3.5 w-3.5 animate-spin text-primary" />
                      Searching database...
                    </div>
                  )}

                  {selectedMedicineName && step.id === 1 && (
                    <div className="flex items-center justify-between rounded-lg border bg-green-50/50 border-green-200 px-3 py-1.5 mt-2 text-xs">
                      <div className="space-y-0.5">
                        <p className="font-semibold text-green-950">
                          Medicine Selected: {selectedMedicineName}
                        </p>
                        <p className="text-[10px] text-green-700">
                          {selectedMedicineSource === "inventory"
                            ? "Found in stock database"
                            : selectedMedicineSource === "doctor_added"
                              ? "Found in doctor-added database"
                              : "Custom medicine added"}
                        </p>
                      </div>
                      <button
                        type="button"
                        onClick={onClearSelectedMedicine}
                        className="rounded-md p-1 hover:bg-green-100 text-green-700 transition-colors"
                      >
                        <X className="h-3.5 w-3.5" />
                      </button>
                    </div>
                  )}

                  {showCustomConfirm && step.id === 1 && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50/70 p-3.5 space-y-3 mt-2 text-xs text-amber-950 shadow-sm">
                      <p className="font-bold flex items-center gap-1.5 text-amber-800">
                        <svg
                          className="w-4 h-4 text-amber-600"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                          strokeWidth="2"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                          />
                        </svg>
                        Medicine Not Found
                      </p>
                      <p className="text-[11px] leading-relaxed">
                        &ldquo;{showCustomConfirm.name}&rdquo; was not found in the
                        database. Do you want to add it as a custom medicine?
                      </p>
                      <div className="flex gap-2">
                        <Button
                          type="button"
                          size="sm"
                          onClick={onCustomConfirmAccept}
                          className="h-7 text-[10px] px-3 font-semibold bg-primary hover:bg-primary/90 text-white rounded-lg shadow-sm"
                        >
                          Yes, Add Custom
                        </Button>
                        <Button
                          type="button"
                          size="sm"
                          variant="outline"
                          onClick={onCustomConfirmDismiss}
                          className="h-7 text-[10px] px-3 font-semibold border-amber-300 text-amber-900 hover:bg-amber-100/50 rounded-lg"
                        >
                          No, Record Again
                        </Button>
                      </div>
                    </div>
                  )}

                  {/* Navigation Action Buttons inline under active step */}
                  <div className="flex items-center justify-between gap-3 pt-2">
                    <Button
                      type="button"
                      size="sm"
                      variant="outline"
                      onClick={onBack}
                      disabled={guidedStep === 1 || controlsBusy}
                      className="h-8 text-xs font-medium"
                    >
                      Back
                    </Button>

                    {guidedStep < guidedVoiceSteps.length ? (
                      <Button
                        type="button"
                        size="sm"
                        onClick={onNext}
                        disabled={controlsBusy}
                        className="h-8 text-xs font-medium"
                      >
                        Next Step
                      </Button>
                    ) : (
                      <Button
                        type="button"
                        size="sm"
                        onClick={onFinish}
                        disabled={
                          controlsBusy ||
                          !Object.values(guidedTranscripts).some((val) =>
                            Boolean(val.trim()),
                          )
                        }
                        className="h-8 text-xs font-medium"
                      >
                        {isFinishing ? (
                          <>
                            <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                            Applying...
                          </>
                        ) : (
                          "Apply to Form"
                        )}
                      </Button>
                    )}
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </div>

      {speechError && (
        <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700 font-medium">
          {speechError}
        </div>
      )}
    </div>
  );
}
