"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { useForm, useWatch } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Loader2, Mic, Search, Stethoscope, Undo2, X } from "lucide-react";
import { useParams } from "next/navigation";

import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { RadioField } from "@/components/custom/RadioField";

import { useAuth } from "@/context/userContext";
import { useAddPrescription } from "@/queries/useAddPrescription";
import { useMedicines } from "@/queries/useMedicines";
import { useParsePrescriptionDraft } from "@/queries/useParsePrescriptionDraft";

const PrescriptionSchema = z.object({
  medicine_id: z.string().optional(),
  medicine_name: z.string().min(2, "Medication required"),
  medication_type: z.string().min(1, "Medication type required"),
  dosage: z.string().min(1, "Dosage required"),
  frequency: z.string().min(1, "Frequency required"),
  timing_morning: z.boolean().optional(),
  timing_afternoon: z.boolean().optional(),
  timing_evening: z.boolean().optional(),
  timing_night: z.boolean().optional(),
  meal: z.enum(["before_meal", "after_meal", "with_meal"], {
    message: "Please select a meal option",
  }),
  instructions: z.string().optional(),
  stamp_preference: z.string().min(1, "Stamp preference required"),
});

export type PrescriptionForm = z.infer<typeof PrescriptionSchema>;

type MedicineItem = {
  id: string;
  name: string;
  type?: string | null;
  source?: "inventory" | "doctor_added";
};

type DraftFormPayload = {
  medicine_id?: string | null;
  medicine_name?: string | null;
  medicine_source?: "inventory" | "doctor_added" | null;
  medication_type?: string | null;
  dosage?: string | null;
  frequency?: string | null;
  timing_morning?: boolean;
  timing_afternoon?: boolean;
  timing_evening?: boolean;
  timing_night?: boolean;
  meal?: PrescriptionForm["meal"] | null;
  instructions?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  stamp_preference?: string | null;
};

type DraftResponsePayload = {
  draft_id?: string | null;
  form?: DraftFormPayload;
  warnings?: string[];
  missing_fields?: string[];
};

type RequestError = {
  response?: {
    data?: {
      errors?: {
        message?: string;
      };
    };
  };
  message?: string;
};

type EntryMode = "voice" | "manual" | null;
type DictationMode = "guided" | "note";
type VoiceLocale = "en-IN" | "hi-IN" | "pa-IN";

type BrowserSpeechRecognitionAlternative = {
  transcript: string;
};

type BrowserSpeechRecognitionResult = {
  isFinal: boolean;
  0: BrowserSpeechRecognitionAlternative;
};

type BrowserSpeechRecognitionEvent = {
  resultIndex: number;
  results: ArrayLike<BrowserSpeechRecognitionResult>;
};

type BrowserSpeechRecognitionErrorEvent = {
  error: string;
};

type BrowserSpeechRecognition = {
  continuous: boolean;
  interimResults: boolean;
  lang: string;
  maxAlternatives: number;
  onresult: ((event: BrowserSpeechRecognitionEvent) => void) | null;
  onerror: ((event: BrowserSpeechRecognitionErrorEvent) => void) | null;
  onend: (() => void) | null;
  start: () => void;
  stop: () => void;
  abort: () => void;
};

type BrowserSpeechRecognitionConstructor = new () => BrowserSpeechRecognition;

declare global {
  interface Window {
    SpeechRecognition?: BrowserSpeechRecognitionConstructor;
    webkitSpeechRecognition?: BrowserSpeechRecognitionConstructor;
  }
}

interface AddPrescriptionDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  assistantConfig?: {
    enabled?: boolean;
    input_mode?: string;
    text_mode_max_chars?: number;
    speech_locale?: string;
    supported_locales?: string[];
    allow_custom_locale?: boolean;
    requires_doctor_review?: boolean;
  } | null;
}

const defaultFormValues: PrescriptionForm = {
  medicine_id: "",
  medicine_name: "",
  medication_type: "tablet",
  dosage: "",
  frequency: "",
  timing_morning: false,
  timing_afternoon: false,
  timing_evening: false,
  timing_night: false,
  meal: undefined as unknown as PrescriptionForm["meal"],
  instructions: "",
  stamp_preference: "only_global",
};

const medicationTypeOptions = [
  { label: "Tablet", value: "tablet" },
  { label: "Capsule", value: "capsule" },
  { label: "Syrup", value: "syrup" },
  { label: "Drop", value: "drop" },
  { label: "Injection", value: "injection" },
  { label: "Cream", value: "cream" },
  { label: "Ointment", value: "ointment" },
  { label: "Other", value: "other" },
];

const frequencyOptions = [
  { label: "Once a day", value: "OD" },
  { label: "Twice a day", value: "BD" },
  { label: "Three times a day", value: "TDS" },
  { label: "SOS", value: "SOS" },
];

const stampOptions = [
  {
    label: "Global Stamp (Default Stamp with Signature)",
    value: "only_global",
  },
  {
    label: "Department Stamp (With Signature)",
    value: "only_department",
  },
  {
    label: "Both (Global & Department Stamp with Signature)",
    value: "both",
  },
];

const mealOptions = [
  { label: "Before Meal", value: "before_meal" },
  { label: "After Meal", value: "after_meal" },
  { label: "With Meal", value: "with_meal" },
];

const voiceLanguageOptions: Array<{ label: string; value: VoiceLocale }> = [
  { label: "English", value: "en-IN" },
  { label: "Hindi", value: "hi-IN" },
  { label: "Punjabi", value: "pa-IN" },
];

const manualSteps = [
  {
    id: 1,
    title: "Medicine",
    description: "Pick the medicine or add a new custom name.",
  },
  {
    id: 2,
    title: "Dosage",
    description: "Set medicine type, dosage, and frequency.",
  },
  {
    id: 3,
    title: "Schedule",
    description: "Choose timings, meal relation, and dates.",
  },
  {
    id: 4,
    title: "Review",
    description: "Add notes, stamp preference, and save.",
  },
];

const guidedVoiceSteps = [
  {
    id: 1,
    title: "Medicine Name",
    hint: "Speak the medicine name clearly. Example: Paracetamol 650.",
  },
  {
    id: 2,
    title: "Type and Dosage",
    hint: "Speak the type and dosage. Example: tablet, one tablet.",
  },
  {
    id: 3,
    title: "Frequency and Timings",
    hint: "Speak when to take it. Example: twice a day, morning and night.",
  },
  {
    id: 4,
    title: "Meal, Duration, Notes",
    hint: "Speak meal relation, duration, and special notes.",
  },
];

const manualStepFields: Record<number, Array<keyof PrescriptionForm>> = {
  1: ["medicine_name", "medication_type"],
  2: ["dosage", "frequency"],
  3: ["meal"],
  4: ["stamp_preference"],
};

export default function AddPrescriptionDialog({
  open,
  onOpenChange,
  assistantConfig,
}: AddPrescriptionDialogProps) {
  const { token } = useAuth();
  const params = useParams();
  const appointmentId = params?.id as string;

  const addPrescription = useAddPrescription(appointmentId || "", token!);
  const parseDraft = useParsePrescriptionDraft(appointmentId || "");

  const assistantMode = assistantConfig?.input_mode || "off";
  const dictationEnabled =
    Boolean(assistantConfig?.enabled) &&
    (assistantMode === "text" || assistantMode === "speech");
  const speechModeEnabled = dictationEnabled && assistantMode === "speech";
  const textModeMaxChars = assistantConfig?.text_mode_max_chars || 1000;

  const [entryMode, setEntryMode] = useState<EntryMode>(
    getDefaultEntryMode(dictationEnabled),
  );
  const [dictationMode, setDictationMode] = useState<DictationMode>("guided");
  const [manualStep, setManualStep] = useState(1);
  const [guidedStep, setGuidedStep] = useState(1);
  const [searchQuery, setSearchQuery] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [startDate, setStartDate] = useState<string>(getTodayDate());
  const [endDate, setEndDate] = useState<string>("");
  const [showSuccess, setShowSuccess] = useState(false);
  const [draftInput, setDraftInput] = useState("");
  const [guidedTranscripts, setGuidedTranscripts] = useState<
    Record<number, string>
  >(createEmptyGuidedTranscripts());
  const [draftId, setDraftId] = useState<string | null>(null);
  const [draftWarnings, setDraftWarnings] = useState<string[]>([]);
  const [draftMissingFields, setDraftMissingFields] = useState<string[]>([]);
  const [isListening, setIsListening] = useState(false);
  const [speechError, setSpeechError] = useState<string | null>(null);
  const [selectedSpeechLocale, setSelectedSpeechLocale] = useState<VoiceLocale>(
    () => getInitialVoiceLocale(assistantConfig?.speech_locale),
  );

  const recognitionRef = useRef<BrowserSpeechRecognition | null>(null);
  const shouldParseAfterStopRef = useRef(false);
  const draftInputRef = useRef("");
  const transcriptBaseRef = useRef("");
  const transcriptFinalRef = useRef("");
  const guidedTranscriptsRef = useRef<Record<number, string>>(
    createEmptyGuidedTranscripts(),
  );

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchQuery.trim());
    }, 350);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  const {
    handleSubmit,
    reset,
    resetField,
    setValue,
    control,
    trigger,
    formState: { errors },
  } = useForm<PrescriptionForm>({
    resolver: zodResolver(PrescriptionSchema),
    defaultValues: defaultFormValues,
  });

  const selectedMedicineName = useWatch({ control, name: "medicine_name" });
  const medicationType = useWatch({ control, name: "medication_type" });
  const meal = useWatch({ control, name: "meal" });
  const dosage = useWatch({ control, name: "dosage" });
  const frequency = useWatch({ control, name: "frequency" });
  const instructions = useWatch({ control, name: "instructions" });
  const stampPreference = useWatch({ control, name: "stamp_preference" });
  const timingMorning = useWatch({ control, name: "timing_morning" });
  const timingAfternoon = useWatch({ control, name: "timing_afternoon" });
  const timingEvening = useWatch({ control, name: "timing_evening" });
  const timingNight = useWatch({ control, name: "timing_night" });

  const lookupSearchTerm = debouncedSearch || selectedMedicineName || "";
  const medicinesQuery = useMedicines({
    page: 1,
    per_page: 20,
    search: lookupSearchTerm,
    include_doctor_added: true,
  });
  const medicineList = useMemo(
    () => medicinesQuery.data?.data || [],
    [medicinesQuery.data?.data],
  );
  const speechSupported =
    typeof window !== "undefined" &&
    Boolean(window.SpeechRecognition || window.webkitSpeechRecognition);

  useEffect(() => {
    draftInputRef.current = draftInput;
  }, [draftInput]);

  useEffect(() => {
    return () => {
      recognitionRef.current?.abort();
      recognitionRef.current = null;
    };
  }, []);

  useEffect(() => {
    if (open) {
      return;
    }

    resetDialogState({
      reset,
      setDraftInput,
      setDraftId,
      setDraftWarnings,
      setDraftMissingFields,
      setSearchQuery,
      setShowSuccess,
      setEntryMode,
      setDictationMode,
      setManualStep,
      setGuidedStep,
      setGuidedTranscripts,
      setSpeechError,
      setSelectedSpeechLocale,
      setStartDate,
      setEndDate,
      dictationEnabled,
      stopListening: () => {
        shouldParseAfterStopRef.current = false;
        recognitionRef.current?.abort();
        recognitionRef.current = null;
        setIsListening(false);
      },
      onResetRefs: () => {
        draftInputRef.current = "";
        guidedTranscriptsRef.current = createEmptyGuidedTranscripts();
      },
      defaultSpeechLocale: getInitialVoiceLocale(
        assistantConfig?.speech_locale,
      ),
    });
  }, [assistantConfig?.speech_locale, dictationEnabled, open, reset]);

  useEffect(() => {
    if (!dictationEnabled && entryMode === "voice") {
      stopListening(false);
      setEntryMode("manual");
    }
  }, [dictationEnabled, entryMode]);

  const dosageOptions = useMemo(() => {
    return getDosageOptions(medicationType);
  }, [medicationType]);

  const exactMedicineMatch = useMemo(() => {
    const normalizedName = normalizeValue(selectedMedicineName);

    if (!normalizedName) {
      return null;
    }

    return (
      medicineList.find(
        (medicine: MedicineItem) =>
          normalizeValue(medicine.name) === normalizedName,
      ) || null
    );
  }, [medicineList, selectedMedicineName]);

  const medicineStatus = useMemo(() => {
    const normalizedName = normalizeValue(selectedMedicineName);

    if (!normalizedName) {
      return null;
    }

    if (exactMedicineMatch?.source === "inventory") {
      return {
        tone: "green" as const,
        title: "Found in medicine inventory",
        description:
          "This medicine already exists in the main medicine database.",
      };
    }

    if (exactMedicineMatch?.source === "doctor_added") {
      return {
        tone: "amber" as const,
        title: "Found in doctor-added medicines",
        description:
          "This custom medicine already exists and will be reused for this doctor.",
      };
    }

    return {
      tone: "blue" as const,
      title: "New custom medicine",
      description:
        "This name was not found in the database. Saving the prescription will add it to doctor-added medicines.",
    };
  }, [exactMedicineMatch, selectedMedicineName]);

  const reviewTimings = [
    timingMorning ? "Morning" : null,
    timingAfternoon ? "Afternoon" : null,
    timingEvening ? "Evening" : null,
    timingNight ? "Night" : null,
  ]
    .filter(Boolean)
    .join(", ");

  const handleSelectMedicine = (medicine: MedicineItem) => {
    setValue(
      "medicine_id",
      medicine.source === "inventory" ? medicine.id : "",
      {
        shouldValidate: true,
      },
    );
    setValue("medicine_name", medicine.name, { shouldValidate: true });
    setValue("medication_type", medicine.type || medicationType || "tablet", {
      shouldValidate: true,
    });
    setSearchQuery("");
  };

  const handleUseCustomMedicine = (medicineName: string) => {
    const value = medicineName.trim();

    if (!value) {
      return;
    }

    setValue("medicine_id", "", { shouldValidate: true });
    setValue("medicine_name", value, { shouldValidate: true });
    setSearchQuery("");
  };

  const clearSelectedMedicine = () => {
    setValue("medicine_id", "", { shouldValidate: true });
    setValue("medicine_name", "", { shouldValidate: true });
    setSearchQuery("");
  };

  const onSubmit = (data: PrescriptionForm) => {
    const timings: string[] = [];

    if (data.timing_morning) timings.push("morning");
    if (data.timing_afternoon) timings.push("afternoon");
    if (data.timing_evening) timings.push("evening");
    if (data.timing_night) timings.push("night");

    const payload = {
      draft_id: draftId,
      stamp_preference: data.stamp_preference,
      medicines: [
        {
          medicine_id: data.medicine_id || null,
          medicine_name: data.medicine_name.trim(),
          medication_type: data.medication_type,
          dosage: data.dosage,
          frequency: data.frequency,
          timings,
          meal: data.meal,
          start_date: startDate || null,
          end_date: endDate || null,
          instructions: data.instructions || "",
        },
      ],
    };

    addPrescription.mutate(payload, {
      onSuccess: () => {
        setShowSuccess(true);
      },
      onError: (error: RequestError) => {
        alert(
          error?.response?.data?.errors?.message ||
            error?.message ||
            "Failed to add prescription. Please try again.",
        );
      },
    });
  };

  const handleSuccessClose = () => {
    onOpenChange(false);
  };

  const applyDraftToForm = (payload: DraftResponsePayload) => {
    const form = payload?.form || {};

    setValue("medicine_id", form.medicine_id || "", { shouldValidate: true });
    setValue("medicine_name", form.medicine_name || "", {
      shouldValidate: true,
    });
    setValue("medication_type", form.medication_type || "tablet", {
      shouldValidate: true,
    });
    setValue("dosage", form.dosage || "", { shouldValidate: true });
    setValue("frequency", form.frequency || "", { shouldValidate: true });
    setValue("timing_morning", Boolean(form.timing_morning));
    setValue("timing_afternoon", Boolean(form.timing_afternoon));
    setValue("timing_evening", Boolean(form.timing_evening));
    setValue("timing_night", Boolean(form.timing_night));

    if (form.meal) {
      setValue("meal", form.meal, { shouldValidate: true });
    } else {
      resetField("meal");
    }

    setValue("instructions", form.instructions || "", { shouldValidate: true });
    setValue("stamp_preference", form.stamp_preference || "only_global", {
      shouldValidate: true,
    });

    setStartDate(form.start_date || getTodayDate());
    setEndDate(form.end_date || "");
    setSearchQuery("");
  };

  const handleParseDraft = (inputText?: string) => {
    if (!dictationEnabled) {
      setSpeechError("Voice prescription is not enabled for this account.");
      return;
    }

    const textToParse = (inputText ?? draftInputRef.current).trim();

    if (!textToParse) {
      return;
    }

    parseDraft.mutate(
      {
        input_text: textToParse,
      },
      {
        onSuccess: (response: { data?: DraftResponsePayload }) => {
          const payload = response?.data || {};
          setDraftId(payload.draft_id || null);
          setDraftWarnings(payload.warnings || []);
          setDraftMissingFields(payload.missing_fields || []);
          applyDraftToForm(payload);
          setEntryMode("manual");
          setManualStep(1);
        },
        onError: (error: RequestError) => {
          alert(
            error?.response?.data?.errors?.message ||
              error?.message ||
              "Failed to parse prescription text.",
          );
        },
      },
    );
  };

  const buildGuidedDraftText = () => {
    return guidedVoiceSteps
      .map((step) => guidedTranscriptsRef.current[step.id] || "")
      .map((value) => value.trim())
      .filter(Boolean)
      .join(" ");
  };

  const handleFinishGuidedPrefill = () => {
    const combined = buildGuidedDraftText();
    setDraftInput(combined);

    if (isListening) {
      shouldParseAfterStopRef.current = true;
      recognitionRef.current?.stop();
      return;
    }

    handleParseDraft(combined);
  };

  function stopListening(shouldPrefill: boolean) {
    shouldParseAfterStopRef.current = shouldPrefill;

    if (!recognitionRef.current) {
      if (shouldPrefill) {
        if (dictationMode === "guided") {
          const combined = buildGuidedDraftText();
          if (combined) {
            handleParseDraft(combined);
          }
        } else if (draftInputRef.current.trim()) {
          handleParseDraft(draftInputRef.current);
        }
      }
      return;
    }

    recognitionRef.current.stop();
  }

  const startListening = () => {
    if (typeof window === "undefined") {
      return;
    }

    const SpeechRecognitionApi =
      window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognitionApi) {
      setSpeechError("This browser does not support voice dictation.");
      return;
    }

    recognitionRef.current?.abort();

    const recognition = new SpeechRecognitionApi();

    if (dictationMode === "guided") {
      transcriptBaseRef.current = (
        guidedTranscriptsRef.current[guidedStep] || ""
      ).trim();
    } else {
      transcriptBaseRef.current = draftInputRef.current.trim();
    }

    transcriptFinalRef.current = "";
    shouldParseAfterStopRef.current = false;

    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = selectedSpeechLocale;
    recognition.maxAlternatives = 1;

    recognition.onresult = (event) => {
      let finalTranscript = transcriptFinalRef.current;
      let interimTranscript = "";

      for (
        let index = event.resultIndex;
        index < event.results.length;
        index += 1
      ) {
        const result = event.results[index];
        const transcript = result?.[0]?.transcript?.trim();

        if (!transcript) {
          continue;
        }

        if (result.isFinal) {
          finalTranscript = combineTranscript(finalTranscript, transcript);
        } else {
          interimTranscript = combineTranscript(interimTranscript, transcript);
        }
      }

      transcriptFinalRef.current = finalTranscript;

      const nextText = combineTranscript(
        transcriptBaseRef.current,
        finalTranscript,
        interimTranscript,
      );

      if (dictationMode === "guided") {
        setGuidedTranscripts((prev) => {
          const updated = {
            ...prev,
            [guidedStep]: nextText,
          };
          guidedTranscriptsRef.current = updated;
          return updated;
        });
      } else {
        setDraftInput(nextText.slice(0, textModeMaxChars));
      }

      setSpeechError(null);
    };

    recognition.onerror = (event) => {
      setSpeechError(
        event.error === "not-allowed"
          ? "Microphone permission was blocked."
          : "Voice capture failed. Please try again.",
      );
    };

    recognition.onend = () => {
      const shouldPrefill = shouldParseAfterStopRef.current;

      shouldParseAfterStopRef.current = false;
      setIsListening(false);
      recognitionRef.current = null;

      if (shouldPrefill) {
        if (dictationMode === "guided") {
          const combined = buildGuidedDraftText();
          if (combined) {
            handleParseDraft(combined);
          }
        } else if (draftInputRef.current.trim()) {
          handleParseDraft(draftInputRef.current);
        }
      }
    };

    recognitionRef.current = recognition;
    setSpeechError(null);
    setIsListening(true);

    try {
      recognition.start();
    } catch {
      recognitionRef.current = null;
      setIsListening(false);
      setSpeechError("Voice capture could not be started. Please try again.");
    }
  };

  const handleNextManualStep = async () => {
    const isValid = await trigger(manualStepFields[manualStep]);

    if (!isValid) {
      return;
    }

    setManualStep((previous) => Math.min(previous + 1, manualSteps.length));
  };

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="w-[95vw] max-w-4xl! rounded-2xl p-0 overflow-hidden">
          <DialogHeader className="border-b px-4 py-4 pr-14 sm:px-6 sm:pr-20">
            <div className="flex items-start justify-between gap-3">
              <div className="space-y-1">
                <DialogTitle className="text-lg sm:text-xl">
                  Add Prescription
                </DialogTitle>
                <p className="text-xs text-muted-foreground sm:text-sm">
                  Choose voice or manual entry. Unknown medicine names will be
                  saved as doctor-added medicines when you submit.
                </p>
              </div>

              {entryMode !== null && dictationEnabled && (
                <button
                  type="button"
                  onClick={() => {
                    stopListening(false);
                    setEntryMode(null);
                  }}
                  className="mr-8 flex items-center gap-2 text-primary font-semibold text-[10px] sm:mr-10 sm:text-xs uppercase tracking-wide hover:translate-x-1 transition-transform shrink-0"
                >
                  <Undo2 className="h-4 w-4 shrink-0" />
                  <span className="whitespace-nowrap">Back to Modes</span>
                </button>
              )}
            </div>
          </DialogHeader>

          <div className="max-h-[82vh] overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
              {entryMode === null && (
                <div className="space-y-4 rounded-2xl border bg-muted/10 p-4">
                  <div>
                    <p className="text-sm font-semibold">
                      How do you want to add this prescription?
                    </p>
                    <p className="text-xs text-muted-foreground">
                      Choose an entry method first. The form will open in the
                      selected mode.
                    </p>
                  </div>

                  <div className="grid gap-3 sm:grid-cols-2">
                    <button
                      type="button"
                      onClick={() => setEntryMode("voice")}
                      disabled={!dictationEnabled}
                      className={`rounded-2xl border p-4 text-left transition ${
                        dictationEnabled
                          ? "border-border bg-background hover:border-primary hover:bg-primary/5"
                          : "cursor-not-allowed border-dashed bg-muted/30 opacity-60"
                      }`}
                    >
                      <div className="flex items-start gap-3">
                        <div className="rounded-xl bg-primary/10 p-2 text-primary">
                          <Mic className="h-5 w-5" />
                        </div>
                        <div className="space-y-1">
                          <p className="text-sm font-semibold">
                            Voice Prescription
                          </p>
                          <p className="text-xs text-muted-foreground">
                            Dictate the prescription, then prefill the manual
                            form for review.
                          </p>
                          {!dictationEnabled && (
                            <p className="text-xs text-amber-700">
                              Voice prescription is unavailable for this
                              account.
                            </p>
                          )}
                        </div>
                      </div>
                    </button>

                    <button
                      type="button"
                      onClick={() => {
                        stopListening(false);
                        setEntryMode("manual");
                      }}
                      className="rounded-2xl border border-border bg-background p-4 text-left transition hover:border-primary hover:bg-primary/5"
                    >
                      <div className="flex items-start gap-3">
                        <div className="rounded-xl bg-primary/10 p-2 text-primary">
                          <Stethoscope className="h-5 w-5" />
                        </div>
                        <div className="space-y-1">
                          <p className="text-sm font-semibold">
                            Manual Prescription
                          </p>
                          <p className="text-xs text-muted-foreground">
                            Fill the prescription step by step in the form.
                          </p>
                        </div>
                      </div>
                    </button>
                  </div>
                </div>
              )}

              {entryMode !== null && (
                <div className="space-y-4">
                  {entryMode === "voice" && dictationEnabled && (
                    <div className="space-y-4 rounded-2xl border bg-muted/10 p-4">
                      <div className="space-y-3">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                          <div>
                            <p className="text-sm font-semibold">
                              Voice Prescription Assistant
                            </p>
                            <p className="text-xs text-muted-foreground">
                              Supports English, Hindi, and Punjabi. Prefill the
                              manual form after dictation and review before
                              saving.
                            </p>
                          </div>

                          <div className="flex items-center gap-2 rounded-xl border bg-background p-1">
                            {voiceLanguageOptions.map((option) => (
                              <button
                                key={option.value}
                                type="button"
                                onClick={() =>
                                  setSelectedSpeechLocale(option.value)
                                }
                                className={`rounded-lg px-3 py-1.5 text-xs font-medium transition ${
                                  selectedSpeechLocale === option.value
                                    ? "bg-primary text-primary-foreground"
                                    : "text-muted-foreground hover:text-foreground"
                                }`}
                              >
                                {option.label}
                              </button>
                            ))}
                          </div>
                        </div>

                        <div className="flex items-center gap-2 rounded-xl border bg-background p-1">
                          <button
                            type="button"
                            onClick={() => {
                              stopListening(false);
                              setDictationMode("guided");
                            }}
                            className={`flex-1 rounded-lg px-3 py-2 text-sm font-medium transition ${
                              dictationMode === "guided"
                                ? "bg-primary text-primary-foreground"
                                : "text-muted-foreground hover:text-foreground"
                            }`}
                          >
                            Guided Steps
                          </button>
                          <button
                            type="button"
                            onClick={() => {
                              stopListening(false);
                              setDictationMode("note");
                            }}
                            className={`flex-1 rounded-lg px-3 py-2 text-sm font-medium transition ${
                              dictationMode === "note"
                                ? "bg-primary text-primary-foreground"
                                : "text-muted-foreground hover:text-foreground"
                            }`}
                          >
                            Full Note
                          </button>
                        </div>
                      </div>

                      {dictationMode === "guided" && (
                        <div className="space-y-4">
                          <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            {guidedVoiceSteps.map((step) => {
                              const isActive = guidedStep === step.id;
                              const isComplete =
                                normalizeValue(guidedTranscripts[step.id]) !==
                                "";

                              return (
                                <button
                                  key={step.id}
                                  type="button"
                                  onClick={() => {
                                    stopListening(false);
                                    setGuidedStep(step.id);
                                  }}
                                  className={`rounded-xl border px-3 py-2 text-left transition ${
                                    isActive
                                      ? "border-primary bg-primary/5"
                                      : "border-border bg-background"
                                  }`}
                                >
                                  <p className="text-xs font-semibold">
                                    Step {step.id}
                                  </p>
                                  <p className="mt-1 text-sm font-medium">
                                    {step.title}
                                  </p>
                                  <p className="mt-1 text-[11px] text-muted-foreground">
                                    {isComplete ? "Captured" : "Pending"}
                                  </p>
                                </button>
                              );
                            })}
                          </div>

                          <div className="rounded-2xl border bg-background p-4">
                            <div className="space-y-1">
                              <p className="text-sm font-semibold">
                                {guidedVoiceSteps[guidedStep - 1]?.title}
                              </p>
                              <p className="text-xs text-muted-foreground">
                                {guidedVoiceSteps[guidedStep - 1]?.hint}
                              </p>
                            </div>

                            <div className="mt-4 flex flex-wrap items-center gap-2">
                              {speechModeEnabled && speechSupported && (
                                <Button
                                  type="button"
                                  variant={
                                    isListening ? "destructive" : "secondary"
                                  }
                                  onClick={() =>
                                    isListening
                                      ? stopListening(false)
                                      : startListening()
                                  }
                                  disabled={parseDraft.isPending}
                                >
                                  {isListening ? (
                                    <>
                                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                      Stop Listening
                                    </>
                                  ) : (
                                    <>
                                      <Mic className="mr-2 h-4 w-4" />
                                      Start Listening
                                    </>
                                  )}
                                </Button>
                              )}

                              <span className="text-xs text-muted-foreground">
                                Language:{" "}
                                {
                                  voiceLanguageOptions.find(
                                    (item) =>
                                      item.value === selectedSpeechLocale,
                                  )?.label
                                }
                              </span>
                            </div>

                            <div className="mt-4 space-y-2">
                              <Label className="text-xs font-semibold text-muted-foreground">
                                Step Transcript
                              </Label>
                              <Textarea
                                value={guidedTranscripts[guidedStep] || ""}
                                onChange={(event) => {
                                  const nextValue = event.target.value;
                                  setGuidedTranscripts((prev) => {
                                    const updated = {
                                      ...prev,
                                      [guidedStep]: nextValue,
                                    };
                                    guidedTranscriptsRef.current = updated;
                                    return updated;
                                  });
                                }}
                                rows={3}
                                className="text-sm"
                                placeholder="Speak here or type the step manually."
                                disabled={isListening}
                              />
                            </div>

                            <div className="mt-4 flex items-center justify-between gap-3">
                              <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                  stopListening(false);
                                  setGuidedStep((previous) =>
                                    Math.max(previous - 1, 1),
                                  );
                                }}
                                disabled={guidedStep === 1 || isListening}
                              >
                                Back
                              </Button>

                              {guidedStep < guidedVoiceSteps.length ? (
                                <Button
                                  type="button"
                                  onClick={() => {
                                    stopListening(false);
                                    setGuidedStep((previous) =>
                                      Math.min(
                                        previous + 1,
                                        guidedVoiceSteps.length,
                                      ),
                                    );
                                  }}
                                  disabled={isListening}
                                >
                                  Next
                                </Button>
                              ) : (
                                <Button
                                  type="button"
                                  onClick={handleFinishGuidedPrefill}
                                  disabled={
                                    parseDraft.isPending ||
                                    !Object.values(guidedTranscripts).some(
                                      (value) => Boolean(value.trim()),
                                    )
                                  }
                                >
                                  {parseDraft.isPending
                                    ? "Prefilling..."
                                    : "Prefill Manual Form"}
                                </Button>
                              )}
                            </div>
                          </div>

                          <div className="rounded-2xl border bg-background p-4">
                            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                              Guided Summary
                            </p>
                            <div className="mt-3 space-y-3">
                              {guidedVoiceSteps.map((step) => (
                                <div key={step.id} className="space-y-1">
                                  <p className="text-xs font-medium text-muted-foreground">
                                    Step {step.id}: {step.title}
                                  </p>
                                  <p className="text-sm">
                                    {guidedTranscripts[step.id] ||
                                      "No input yet"}
                                  </p>
                                </div>
                              ))}
                            </div>
                          </div>
                        </div>
                      )}

                      {dictationMode === "note" && (
                        <div className="space-y-4">
                          {speechModeEnabled && (
                            <div className="flex flex-wrap items-center gap-2">
                              {speechSupported && (
                                <Button
                                  type="button"
                                  variant={
                                    isListening ? "destructive" : "secondary"
                                  }
                                  onClick={() =>
                                    isListening
                                      ? stopListening(true)
                                      : startListening()
                                  }
                                  disabled={parseDraft.isPending}
                                >
                                  {isListening ? (
                                    <>
                                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                      Stop and Prefill
                                    </>
                                  ) : (
                                    <>
                                      <Mic className="mr-2 h-4 w-4" />
                                      Start Listening
                                    </>
                                  )}
                                </Button>
                              )}

                              <span className="text-xs text-muted-foreground">
                                Language:{" "}
                                {
                                  voiceLanguageOptions.find(
                                    (item) =>
                                      item.value === selectedSpeechLocale,
                                  )?.label
                                }
                              </span>
                            </div>
                          )}

                          <Textarea
                            value={draftInput}
                            onChange={(event) =>
                              setDraftInput(
                                event.target.value.slice(0, textModeMaxChars),
                              )
                            }
                            rows={6}
                            className="text-sm"
                            placeholder="Type or dictate the complete prescription note here."
                            disabled={isListening}
                          />

                          <div className="flex items-center justify-between gap-3">
                            <p className="text-xs text-muted-foreground">
                              {draftInput.length}/{textModeMaxChars} characters
                            </p>
                            <Button
                              type="button"
                              onClick={() => handleParseDraft()}
                              disabled={
                                !draftInput.trim() ||
                                parseDraft.isPending ||
                                isListening
                              }
                            >
                              {parseDraft.isPending
                                ? "Prefilling..."
                                : "Prefill Manual Form"}
                            </Button>
                          </div>
                        </div>
                      )}

                      {speechError && (
                        <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                          {speechError}
                        </div>
                      )}
                    </div>
                  )}

                  {entryMode === "manual" && (
                    <div className="space-y-4">
                      {(draftWarnings.length > 0 ||
                        draftMissingFields.length > 0) && (
                        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                          <p className="font-semibold">
                            Review Prefilled Details
                          </p>
                          {draftWarnings.length > 0 && (
                            <ul className="mt-2 list-disc pl-5">
                              {draftWarnings.map((warning) => (
                                <li key={warning}>{warning}</li>
                              ))}
                            </ul>
                          )}
                          {draftMissingFields.length > 0 && (
                            <p className="mt-2 text-xs sm:text-sm">
                              Missing fields: {draftMissingFields.join(", ")}.
                              Review the steps below before saving.
                            </p>
                          )}
                        </div>
                      )}

                      <div className="grid gap-2 sm:grid-cols-4">
                        {manualSteps.map((step) => {
                          const isActive = manualStep === step.id;
                          const isComplete = manualStep > step.id;

                          return (
                            <button
                              key={step.id}
                              type="button"
                              onClick={() => setManualStep(step.id)}
                              className={`rounded-2xl border px-4 py-3 text-left transition ${
                                isActive
                                  ? "border-primary bg-primary/5"
                                  : "border-border bg-background"
                              }`}
                            >
                              <p className="text-xs font-semibold">
                                Step {step.id}
                              </p>
                              <p className="mt-1 text-sm font-semibold">
                                {step.title}
                              </p>
                              <p className="mt-1 text-xs text-muted-foreground">
                                {isComplete ? "Ready" : step.description}
                              </p>
                            </button>
                          );
                        })}
                      </div>

                      <div className="rounded-2xl border bg-background p-4 sm:p-5">
                        <div className="mb-4">
                          <p className="text-sm font-semibold">
                            Step {manualStep}:{" "}
                            {manualSteps[manualStep - 1]?.title}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {manualSteps[manualStep - 1]?.description}
                          </p>
                        </div>

                        {manualStep === 1 && (
                          <div className="space-y-4">
                            <div className="space-y-2">
                              <Label>Medicine</Label>

                              {selectedMedicineName ? (
                                <div className="flex items-center justify-between rounded-xl border bg-muted/30 px-3 py-2">
                                  <div>
                                    <p className="text-sm font-medium">
                                      {selectedMedicineName}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                      {exactMedicineMatch?.source ===
                                      "inventory"
                                        ? "Matched from medicine inventory"
                                        : exactMedicineMatch?.source ===
                                            "doctor_added"
                                          ? "Matched from doctor-added medicines"
                                          : "Custom medicine name"}
                                    </p>
                                  </div>
                                  <button
                                    type="button"
                                    onClick={clearSelectedMedicine}
                                    className="rounded-md p-1 text-muted-foreground hover:bg-muted"
                                  >
                                    <X className="h-4 w-4" />
                                  </button>
                                </div>
                              ) : (
                                <div className="space-y-2">
                                  <div className="relative">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                      placeholder="Search or type medicine name"
                                      value={searchQuery}
                                      onChange={(event) =>
                                        setSearchQuery(event.target.value)
                                      }
                                      className="pl-9"
                                    />
                                  </div>

                                  {searchQuery.trim() !== "" && (
                                    <div className="max-h-56 overflow-y-auto rounded-xl border bg-background">
                                      <button
                                        type="button"
                                        onClick={() =>
                                          handleUseCustomMedicine(searchQuery)
                                        }
                                        className="flex w-full items-center justify-between border-b px-3 py-2 text-left hover:bg-muted/40"
                                      >
                                        <div>
                                          <p className="text-sm font-medium">
                                            Use custom medicine: &quot;
                                            {searchQuery.trim()}&quot;
                                          </p>
                                          <p className="text-xs text-muted-foreground">
                                            Save to doctor-added medicines if
                                            not found
                                          </p>
                                        </div>
                                        <Stethoscope className="h-4 w-4 text-primary" />
                                      </button>

                                      {medicinesQuery.isLoading ? (
                                        <div className="px-3 py-3 text-sm text-muted-foreground">
                                          Checking medicine database...
                                        </div>
                                      ) : medicineList.length > 0 ? (
                                        medicineList.map(
                                          (medicine: MedicineItem) => (
                                            <button
                                              key={`${medicine.source}-${medicine.id}`}
                                              type="button"
                                              onClick={() =>
                                                handleSelectMedicine(medicine)
                                              }
                                              className="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-muted/40"
                                            >
                                              <div>
                                                <p className="text-sm font-medium">
                                                  {medicine.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                  {medicine.source ===
                                                  "inventory"
                                                    ? "Medicine inventory"
                                                    : "Doctor-added medicine"}
                                                </p>
                                              </div>
                                              <span className="text-xs text-muted-foreground">
                                                {medicine.type || "Custom"}
                                              </span>
                                            </button>
                                          ),
                                        )
                                      ) : (
                                        <div className="px-3 py-3 text-sm text-muted-foreground">
                                          No matching medicines found
                                        </div>
                                      )}
                                    </div>
                                  )}
                                </div>
                              )}

                              {errors.medicine_name && (
                                <p className="text-sm text-red-500">
                                  {errors.medicine_name.message}
                                </p>
                              )}
                            </div>

                            {medicineStatus && (
                              <MedicineStatusCard
                                tone={medicineStatus.tone}
                                title={medicineStatus.title}
                                description={medicineStatus.description}
                              />
                            )}

                            <div className="space-y-2">
                              <Label>Medicine Type</Label>
                              <Select
                                value={medicationType}
                                onValueChange={(value) =>
                                  setValue("medication_type", value, {
                                    shouldValidate: true,
                                  })
                                }
                              >
                                <SelectTrigger>
                                  <SelectValue placeholder="Select medicine type" />
                                </SelectTrigger>
                                <SelectContent>
                                  {medicationTypeOptions.map((item) => (
                                    <SelectItem
                                      key={item.value}
                                      value={item.value}
                                    >
                                      {item.label}
                                    </SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                              {errors.medication_type && (
                                <p className="text-sm text-red-500">
                                  {errors.medication_type.message}
                                </p>
                              )}
                            </div>
                          </div>
                        )}

                        {manualStep === 2 && (
                          <div className="space-y-4">
                            <div className="space-y-2">
                              <Label>
                                Dosage{" "}
                                {medicationType ? `(${medicationType})` : ""}
                              </Label>
                              <Select
                                value={dosage}
                                onValueChange={(value) =>
                                  setValue("dosage", value, {
                                    shouldValidate: true,
                                  })
                                }
                              >
                                <SelectTrigger>
                                  <SelectValue placeholder="Select dosage" />
                                </SelectTrigger>
                                <SelectContent>
                                  {dosageOptions.map((item) => (
                                    <SelectItem
                                      key={item.value}
                                      value={item.value}
                                    >
                                      {item.label}
                                    </SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                              {errors.dosage && (
                                <p className="text-sm text-red-500">
                                  {errors.dosage.message}
                                </p>
                              )}
                            </div>

                            <div className="space-y-2">
                              <Label>Frequency</Label>
                              <Select
                                value={frequency}
                                onValueChange={(value) =>
                                  setValue("frequency", value, {
                                    shouldValidate: true,
                                  })
                                }
                              >
                                <SelectTrigger>
                                  <SelectValue placeholder="Select frequency" />
                                </SelectTrigger>
                                <SelectContent>
                                  {frequencyOptions.map((item) => (
                                    <SelectItem
                                      key={item.value}
                                      value={item.value}
                                    >
                                      {item.label}
                                    </SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                              {errors.frequency && (
                                <p className="text-sm text-red-500">
                                  {errors.frequency.message}
                                </p>
                              )}
                            </div>
                          </div>
                        )}

                        {manualStep === 3 && (
                          <div className="space-y-5">
                            <div className="space-y-3">
                              <Label>Timings</Label>
                              <div className="grid grid-cols-2 gap-3">
                                <CheckboxField
                                  label="Morning"
                                  checked={!!timingMorning}
                                  onCheckedChange={(checked) =>
                                    setValue("timing_morning", !!checked)
                                  }
                                />
                                <CheckboxField
                                  label="Afternoon"
                                  checked={!!timingAfternoon}
                                  onCheckedChange={(checked) =>
                                    setValue("timing_afternoon", !!checked)
                                  }
                                />
                                <CheckboxField
                                  label="Evening"
                                  checked={!!timingEvening}
                                  onCheckedChange={(checked) =>
                                    setValue("timing_evening", !!checked)
                                  }
                                />
                                <CheckboxField
                                  label="Night"
                                  checked={!!timingNight}
                                  onCheckedChange={(checked) =>
                                    setValue("timing_night", !!checked)
                                  }
                                />
                              </div>
                            </div>

                            <RadioField
                              label="Meal *"
                              value={meal || ""}
                              onChange={(value) =>
                                setValue(
                                  "meal",
                                  value as PrescriptionForm["meal"],
                                  {
                                    shouldValidate: true,
                                  },
                                )
                              }
                              options={mealOptions}
                              direction="row"
                              error={errors.meal?.message}
                            />

                            <div className="grid gap-4 sm:grid-cols-2">
                              <div className="space-y-2">
                                <Label>Start Date</Label>
                                <Input
                                  type="date"
                                  min={getTodayDate()}
                                  value={startDate}
                                  onChange={(event) =>
                                    setStartDate(event.target.value)
                                  }
                                />
                              </div>

                              <div className="space-y-2">
                                <Label>End Date</Label>
                                <Input
                                  type="date"
                                  min={startDate || getTodayDate()}
                                  value={endDate}
                                  onChange={(event) =>
                                    setEndDate(event.target.value)
                                  }
                                />
                              </div>
                            </div>
                          </div>
                        )}

                        {manualStep === 4 && (
                          <div className="space-y-5">
                            <div className="space-y-2">
                              <Label>Notes</Label>
                              <Textarea
                                rows={4}
                                value={instructions || ""}
                                onChange={(event) =>
                                  setValue("instructions", event.target.value, {
                                    shouldValidate: true,
                                  })
                                }
                                placeholder="Write additional instructions for the patient."
                              />
                            </div>

                            <div className="space-y-2">
                              <Label>Stamp Preference</Label>
                              <Select
                                value={stampPreference}
                                onValueChange={(value) =>
                                  setValue("stamp_preference", value, {
                                    shouldValidate: true,
                                  })
                                }
                              >
                                <SelectTrigger>
                                  <SelectValue placeholder="Select stamp preference" />
                                </SelectTrigger>
                                <SelectContent>
                                  {stampOptions.map((item) => (
                                    <SelectItem
                                      key={item.value}
                                      value={item.value}
                                    >
                                      {item.label}
                                    </SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                              {errors.stamp_preference && (
                                <p className="text-sm text-red-500">
                                  {errors.stamp_preference.message}
                                </p>
                              )}
                            </div>

                            <div className="rounded-2xl border bg-muted/20 p-4">
                              <p className="text-sm font-semibold">Review</p>
                              <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                <ReviewRow
                                  label="Medicine"
                                  value={selectedMedicineName || "Not selected"}
                                />
                                <ReviewRow
                                  label="Type"
                                  value={medicationType || "Not selected"}
                                />
                                <ReviewRow
                                  label="Dosage"
                                  value={dosage || "Not selected"}
                                />
                                <ReviewRow
                                  label="Frequency"
                                  value={frequency || "Not selected"}
                                />
                                <ReviewRow
                                  label="Timings"
                                  value={reviewTimings || "Not specified"}
                                />
                                <ReviewRow
                                  label="Meal"
                                  value={
                                    meal
                                      ? meal.replace("_", " ")
                                      : "Not selected"
                                  }
                                />
                                <ReviewRow
                                  label="Start Date"
                                  value={startDate || "Not specified"}
                                />
                                <ReviewRow
                                  label="End Date"
                                  value={endDate || "Open"}
                                />
                              </div>
                            </div>
                          </div>
                        )}

                        <div className="mt-6 flex items-center justify-between gap-3">
                          <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                              setManualStep((previous) =>
                                Math.max(previous - 1, 1),
                              )
                            }
                            disabled={manualStep === 1}
                          >
                            Back
                          </Button>

                          {manualStep < manualSteps.length ? (
                            <Button
                              type="button"
                              onClick={handleNextManualStep}
                            >
                              Next
                            </Button>
                          ) : (
                            <Button
                              type="submit"
                              disabled={addPrescription.isPending}
                            >
                              {addPrescription.isPending
                                ? "Saving..."
                                : "Add Prescription"}
                            </Button>
                          )}
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </form>
          </div>
        </DialogContent>
      </Dialog>

      <SuccessDialog open={showSuccess} onClose={handleSuccessClose} />
    </>
  );
}

function CheckboxField({
  label,
  checked,
  onCheckedChange,
}: {
  label: string;
  checked: boolean;
  onCheckedChange: (checked: boolean) => void;
}) {
  return (
    <div className="flex items-center space-x-2 rounded-xl border p-3">
      <Checkbox checked={checked} onCheckedChange={onCheckedChange} />
      <Label className="text-sm">{label}</Label>
    </div>
  );
}

function MedicineStatusCard({
  tone,
  title,
  description,
}: {
  tone: "green" | "amber" | "blue";
  title: string;
  description: string;
}) {
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

function ReviewRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="space-y-1">
      <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        {label}
      </p>
      <p className="text-sm">{value}</p>
    </div>
  );
}

function SuccessDialog({
  open,
  onClose,
}: {
  open: boolean;
  onClose: () => void;
}) {
  return (
    <Dialog open={open} onOpenChange={onClose}>
      <DialogContent className="w-[90vw] max-w-sm rounded-xl">
        <DialogHeader>
          <DialogTitle>Prescription Added</DialogTitle>
        </DialogHeader>
        <p className="text-sm text-muted-foreground">
          The prescription has been added successfully.
        </p>
        <Button onClick={onClose} className="mt-4 w-full">
          OK
        </Button>
      </DialogContent>
    </Dialog>
  );
}

function createEmptyGuidedTranscripts() {
  return {
    1: "",
    2: "",
    3: "",
    4: "",
  };
}

function resetDialogState({
  reset,
  setDraftInput,
  setDraftId,
  setDraftWarnings,
  setDraftMissingFields,
  setSearchQuery,
  setShowSuccess,
  setEntryMode,
  setDictationMode,
  setManualStep,
  setGuidedStep,
  setGuidedTranscripts,
  setSpeechError,
  setSelectedSpeechLocale,
  setStartDate,
  setEndDate,
  dictationEnabled,
  stopListening,
  onResetRefs,
  defaultSpeechLocale,
}: {
  reset: (values?: PrescriptionForm) => void;
  setDraftInput: (value: string) => void;
  setDraftId: (value: string | null) => void;
  setDraftWarnings: (value: string[]) => void;
  setDraftMissingFields: (value: string[]) => void;
  setSearchQuery: (value: string) => void;
  setShowSuccess: (value: boolean) => void;
  setEntryMode: (value: EntryMode) => void;
  setDictationMode: (value: DictationMode) => void;
  setManualStep: (value: number) => void;
  setGuidedStep: (value: number) => void;
  setGuidedTranscripts: (value: Record<number, string>) => void;
  setSpeechError: (value: string | null) => void;
  setSelectedSpeechLocale: (value: VoiceLocale) => void;
  setStartDate: (value: string) => void;
  setEndDate: (value: string) => void;
  dictationEnabled: boolean;
  stopListening: () => void;
  onResetRefs: () => void;
  defaultSpeechLocale: VoiceLocale;
}) {
  stopListening();
  onResetRefs();
  reset(defaultFormValues);
  setDraftInput("");
  setDraftId(null);
  setDraftWarnings([]);
  setDraftMissingFields([]);
  setSearchQuery("");
  setShowSuccess(false);
  setEntryMode(getDefaultEntryMode(dictationEnabled));
  setDictationMode("guided");
  setManualStep(1);
  setGuidedStep(1);
  setGuidedTranscripts(createEmptyGuidedTranscripts());
  setSpeechError(null);
  setSelectedSpeechLocale(defaultSpeechLocale);
  setStartDate(getTodayDate());
  setEndDate("");
}

function normalizeValue(value?: string | null) {
  return (value || "").trim().toLowerCase();
}

function getDefaultEntryMode(dictationEnabled: boolean): EntryMode {
  return dictationEnabled ? null : "manual";
}

function combineTranscript(...parts: Array<string | null | undefined>) {
  return parts
    .map((part) => (part || "").trim())
    .filter(Boolean)
    .join(" ")
    .replace(/\s+/g, " ")
    .trim();
}

function isVoiceLocale(value?: string | null): value is VoiceLocale {
  return value === "en-IN" || value === "hi-IN" || value === "pa-IN";
}

function getInitialVoiceLocale(value?: string | null): VoiceLocale {
  return isVoiceLocale(value) ? value : "en-IN";
}

function getTodayDate() {
  return new Date().toISOString().split("T")[0];
}

function getDosageOptions(type: string) {
  const normalizedType = (type || "").toLowerCase();

  if (normalizedType.includes("tablet") || normalizedType.includes("capsule")) {
    return [
      { label: "1/2 Tablet", value: "0.5 tablet" },
      { label: "1 Tablet", value: "1 tablet" },
      { label: "1 1/2 Tablets", value: "1.5 tablets" },
      { label: "2 Tablets", value: "2 tablets" },
      { label: "3 Tablets", value: "3 tablets" },
    ];
  }

  if (
    normalizedType.includes("liquid") ||
    normalizedType.includes("syrup") ||
    normalizedType.includes("suspension") ||
    normalizedType.includes("solution")
  ) {
    return [
      { label: "2.5 ml (1/2 spoon)", value: "2.5 ml" },
      { label: "5 ml (1 spoon)", value: "5 ml" },
      { label: "10 ml (2 spoons)", value: "10 ml" },
      { label: "15 ml (3 spoons)", value: "15 ml" },
      { label: "20 ml (4 spoons)", value: "20 ml" },
    ];
  }

  if (normalizedType.includes("drop")) {
    return [
      { label: "1 Drop", value: "1 drop" },
      { label: "2 Drops", value: "2 drops" },
      { label: "3 Drops", value: "3 drops" },
      { label: "4 Drops", value: "4 drops" },
    ];
  }

  if (
    normalizedType.includes("cream") ||
    normalizedType.includes("ointment") ||
    normalizedType.includes("gel")
  ) {
    return [
      { label: "Thin layer", value: "thin layer" },
      { label: "Pea-sized amount", value: "pea-sized amount" },
      { label: "As prescribed", value: "as prescribed" },
    ];
  }

  if (normalizedType.includes("injection")) {
    return [
      { label: "1 Unit", value: "1 unit" },
      { label: "2 Units", value: "2 units" },
      { label: "As prescribed", value: "as prescribed" },
    ];
  }

  return [
    { label: "1 Unit", value: "1 unit" },
    { label: "2 Units", value: "2 units" },
    { label: "As prescribed", value: "as prescribed" },
  ];
}
