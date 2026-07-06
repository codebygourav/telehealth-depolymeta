"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Loader2, Mic, X } from "lucide-react";
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
};

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

export default function AddPrescriptionDialog({
  open,
  onOpenChange,
  assistantConfig,
}: AddPrescriptionDialogProps) {
  const { token } = useAuth();
  const params = useParams();
  const appointment_id = params?.id as string;

  const addPrescription = useAddPrescription(appointment_id || "", token!);
  const parseDraft = useParsePrescriptionDraft(appointment_id || "");

  const [selectedType, setSelectedType] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [startDate, setStartDate] = useState<string>(getTodayDate());
  const [endDate, setEndDate] = useState<string>("");
  const [showSuccess, setShowSuccess] = useState(false);
  const [draftInput, setDraftInput] = useState("");
  const [draftId, setDraftId] = useState<string | null>(null);
  const [draftWarnings, setDraftWarnings] = useState<string[]>([]);
  const [draftMissingFields, setDraftMissingFields] = useState<string[]>([]);
  const [speechSupported, setSpeechSupported] = useState(false);
  const [isListening, setIsListening] = useState(false);
  const [speechError, setSpeechError] = useState<string | null>(null);
  const [selectedSpeechLocale, setSelectedSpeechLocale] = useState("auto");
  const [customSpeechLocale, setCustomSpeechLocale] = useState("");
  const recognitionRef = useRef<BrowserSpeechRecognition | null>(null);
  const shouldParseAfterStopRef = useRef(false);
  const draftInputRef = useRef("");
  const transcriptBaseRef = useRef("");
  const transcriptFinalRef = useRef("");

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchQuery);
    }, 500);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  const medicinesQuery = useMedicines({
    page: 1,
    per_page: 20,
    search: debouncedSearch,
  });

  const {
    handleSubmit,
    reset,
    setValue,
    watch,
    formState: { errors },
  } = useForm<PrescriptionForm>({
    resolver: zodResolver(PrescriptionSchema),
    defaultValues: {
      medicine_id: "",
      medicine_name: "",
      medication_type: "tablet",
      dosage: "",
      frequency: "",
      timing_morning: false,
      timing_afternoon: false,
      timing_evening: false,
      timing_night: false,
      meal: undefined,
      instructions: "",
      stamp_preference: "only_global",
    },
  });

  const medicationType = watch("medication_type");
  const meal = watch("meal");
  const selectedMedicineName = watch("medicine_name");

  useEffect(() => {
    setValue("dosage", "");
  }, [medicationType, setValue]);

  useEffect(() => {
    draftInputRef.current = draftInput;
  }, [draftInput]);

  useEffect(() => {
    if (!open) {
      stopListening(false);
      setDraftInput("");
      setDraftId(null);
      setDraftWarnings([]);
      setDraftMissingFields([]);
      setSpeechError(null);
    }
  }, [open]);

  useEffect(() => {
    const configuredLocale = assistantConfig?.speech_locale?.trim() || "en-IN";
    const supportedLocales = assistantConfig?.supported_locales || [];

    if (supportedLocales.includes(configuredLocale)) {
      setSelectedSpeechLocale(configuredLocale);
      setCustomSpeechLocale("");
      return;
    }

    if (supportedLocales.includes("auto")) {
      setSelectedSpeechLocale("auto");
      setCustomSpeechLocale(configuredLocale === "auto" ? "" : configuredLocale);
      return;
    }

    setSelectedSpeechLocale(configuredLocale);
    setCustomSpeechLocale("");
  }, [assistantConfig?.speech_locale, assistantConfig?.supported_locales]);

  useEffect(() => {
    if (typeof window === "undefined") {
      return;
    }

    const SpeechRecognitionApi =
      window.SpeechRecognition || window.webkitSpeechRecognition;

    setSpeechSupported(Boolean(SpeechRecognitionApi));

    return () => {
      recognitionRef.current?.abort();
      recognitionRef.current = null;
    };
  }, []);

  const dosageOptions = useMemo(() => {
    return getDosageOptions(medicationType);
  }, [medicationType]);

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

  const handleSelectMedicine = (medicine: MedicineItem) => {
    setValue("medicine_id", medicine.id, { shouldValidate: true });
    setValue("medicine_name", medicine.name, { shouldValidate: true });
    setValue("medication_type", medicine.type || "tablet", {
      shouldValidate: true,
    });
    setSelectedType(medicine.type || "tablet");
    setSearchQuery("");
  };

  const clearSelectedMedicine = () => {
    setValue("medicine_id", "");
    setValue("medicine_name", "");
    setValue("medication_type", "tablet");
    setSelectedType(null);
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
          medicine_id: data.medicine_id === "custom" || !data.medicine_id ? null : data.medicine_id,
          medicine_name: data.medicine_name,
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
      onError: (error: any) => {
        alert(
          error?.response?.data?.errors?.message ||
          error?.message ||
          "Failed to add prescription. Please try again."
        );
      },
    });
  };

  const handleSuccessClose = () => {
    setShowSuccess(false);
    reset();
    setStartDate(getTodayDate());
    setEndDate("");
    setSelectedType(null);
    setDraftInput("");
    setDraftId(null);
    setDraftWarnings([]);
    setDraftMissingFields([]);
    onOpenChange(false);
  };

  const medicineList = medicinesQuery.data?.data || [];
  const assistantMode = assistantConfig?.input_mode || "off";
  const dictationEnabled =
    Boolean(assistantConfig?.enabled) &&
    (assistantMode === "text" || assistantMode === "speech");
  const speechModeEnabled = dictationEnabled && assistantMode === "speech";
  const textModeMaxChars = assistantConfig?.text_mode_max_chars || 1000;
  const defaultSpeechLocale = assistantConfig?.speech_locale || "en-IN";
  const supportedSpeechLocales =
    assistantConfig?.supported_locales?.length
      ? assistantConfig.supported_locales
      : ["auto", "en-IN", "hi-IN", "pa-IN"];
  const allowCustomSpeechLocale = assistantConfig?.allow_custom_locale !== false;

  const localeLabelMap: Record<string, string> = {
    auto: "Auto (Browser Language)",
    "en-IN": "English (India)",
    "hi-IN": "Hindi (हिंदी)",
    "pa-IN": "Punjabi (ਪੰਜਾਬੀ)",
  };

  const speechLocaleOptions = [
    ...supportedSpeechLocales.map((locale) => ({
      value: locale,
      label: localeLabelMap[locale] || locale,
    })),
    ...(allowCustomSpeechLocale ? [{ value: "custom", label: "Custom Locale" }] : []),
  ];

  const combineTranscript = (...parts: Array<string | null | undefined>) =>
    parts
      .map((part) => (part || "").trim())
      .filter(Boolean)
      .join(" ")
      .replace(/\s+/g, " ")
      .trim();

  const resolvedSpeechLocale = (() => {
    if (selectedSpeechLocale === "custom") {
      return customSpeechLocale.trim() || defaultSpeechLocale;
    }

    if (selectedSpeechLocale === "auto") {
      if (typeof window !== "undefined" && navigator.language) {
        return navigator.language;
      }

      return defaultSpeechLocale;
    }

    return selectedSpeechLocale || defaultSpeechLocale;
  })();

  const applyDraftToForm = (payload: any) => {
    const form = payload?.form || {};

    setValue("medicine_id", form.medicine_id || "custom", { shouldValidate: true });
    setValue("medicine_name", form.medicine_name || "", { shouldValidate: true });
    setValue("medication_type", form.medication_type || "tablet", { shouldValidate: true });
    setValue("dosage", form.dosage || "", { shouldValidate: true });
    setValue("frequency", form.frequency || "", { shouldValidate: true });
    setValue("timing_morning", Boolean(form.timing_morning));
    setValue("timing_afternoon", Boolean(form.timing_afternoon));
    setValue("timing_evening", Boolean(form.timing_evening));
    setValue("timing_night", Boolean(form.timing_night));

    if (form.meal) {
      setValue("meal", form.meal, { shouldValidate: true });
    }

    setValue("instructions", form.instructions || "", { shouldValidate: true });
    setValue("stamp_preference", form.stamp_preference || "only_global", {
      shouldValidate: true,
    });

    setSelectedType(form.medication_type || "tablet");
    setStartDate(form.start_date || getTodayDate());
    setEndDate(form.end_date || "");
  };

  const handleParseDraft = (inputText?: string) => {
    const textToParse = (inputText ?? draftInputRef.current).trim();

    if (!textToParse) {
      return;
    }

    parseDraft.mutate(
      {
        input_text: textToParse,
      },
      {
        onSuccess: (response: any) => {
          const payload = response?.data || {};
          setDraftId(payload.draft_id || null);
          setDraftWarnings(payload.warnings || []);
          setDraftMissingFields(payload.missing_fields || []);
          applyDraftToForm(payload);
        },
        onError: (error: any) => {
          alert(
            error?.response?.data?.errors?.message ||
            error?.message ||
            "Failed to parse prescription text."
          );
        },
      }
    );
  };

  const stopListening = (shouldPrefill: boolean) => {
    shouldParseAfterStopRef.current = shouldPrefill;

    if (!recognitionRef.current) {
      if (shouldPrefill && draftInputRef.current.trim()) {
        handleParseDraft(draftInputRef.current);
      }

      return;
    }

    recognitionRef.current.stop();
  };

  const startListening = () => {
    if (typeof window === "undefined") {
      return;
    }

    const SpeechRecognitionApi =
      window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognitionApi) {
      setSpeechSupported(false);
      setSpeechError("This browser does not support voice dictation.");
      return;
    }

    recognitionRef.current?.abort();

    const recognition = new SpeechRecognitionApi();
    transcriptBaseRef.current = draftInputRef.current.trim();
    transcriptFinalRef.current = "";
    shouldParseAfterStopRef.current = false;

    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = resolvedSpeechLocale;
    recognition.maxAlternatives = 1;

    recognition.onresult = (event) => {
      let finalTranscript = transcriptFinalRef.current;
      let interimTranscript = "";

      for (let index = event.resultIndex; index < event.results.length; index += 1) {
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

      const nextDraft = combineTranscript(
        transcriptBaseRef.current,
        finalTranscript,
        interimTranscript
      ).slice(0, textModeMaxChars);

      setDraftInput(nextDraft);
      setSpeechError(null);
    };

    recognition.onerror = (event) => {
      setSpeechError(
        event.error === "not-allowed"
          ? "Microphone permission was blocked."
          : "Voice capture failed. Please try again."
      );
    };

    recognition.onend = () => {
      const shouldPrefill = shouldParseAfterStopRef.current;

      shouldParseAfterStopRef.current = false;
      setIsListening(false);
      recognitionRef.current = null;

      if (shouldPrefill && draftInputRef.current.trim()) {
        handleParseDraft(draftInputRef.current);
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

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="w-[95vw] max-w-2xl! p-0 overflow-hidden rounded-xl sm:rounded-2xl">
          <DialogHeader className="border-b px-4 sm:px-6 py-3 sm:py-4">
            <div className="flex items-center justify-between">
              <DialogTitle className="text-base sm:text-lg md:text-xl">
                Add Prescription
              </DialogTitle>
            </div>
          </DialogHeader>

          <div className="max-h-[80vh] overflow-y-auto px-4 sm:px-6 py-4 sm:py-5">
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 sm:space-y-5">
              {dictationEnabled && (
                <div className="space-y-2 rounded-lg border border-dashed border-primary/40 bg-primary/5 p-3 sm:p-4">
                  <div className="space-y-1">
                    <Label className="text-xs sm:text-sm font-semibold">
                      Prescription Dictation Assistant
                    </Label>
                    <p className="text-[11px] sm:text-sm text-muted-foreground">
                      {speechModeEnabled
                        ? "Use the microphone to capture the doctor's voice in any browser-supported language. The transcript appears below, then the form is autofilled only after the doctor clicks Done."
                        : "Paste the dictated prescription text here. The system will prefill the form, but the doctor must review every field before saving."}
                    </p>
                  </div>

                  {speechModeEnabled && (
                    <div className="space-y-3">
                      <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                        <div className="space-y-1.5">
                          <Label className="text-[11px] sm:text-xs font-medium text-muted-foreground">
                            Speech Language
                          </Label>
                          <Select
                            value={selectedSpeechLocale}
                            onValueChange={setSelectedSpeechLocale}
                            disabled={isListening}
                          >
                            <SelectTrigger className="h-8 sm:h-9 text-xs sm:text-sm">
                              <SelectValue placeholder="Select language" />
                            </SelectTrigger>
                            <SelectContent>
                              {speechLocaleOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value} className="text-xs sm:text-sm">
                                  {option.label}
                                </SelectItem>
                              ))}
                            </SelectContent>
                          </Select>
                        </div>

                        {selectedSpeechLocale === "custom" && (
                          <div className="space-y-1.5">
                            <Label className="text-[11px] sm:text-xs font-medium text-muted-foreground">
                              Custom Locale
                            </Label>
                            <Input
                              value={customSpeechLocale}
                              onChange={(event) => setCustomSpeechLocale(event.target.value)}
                              placeholder="Example: fr-FR, ta-IN, ar-SA"
                              disabled={isListening}
                              className="h-8 sm:h-9 text-xs sm:text-sm"
                            />
                          </div>
                        )}
                      </div>

                      <div className="flex flex-wrap items-center gap-2">
                        <Button
                          type="button"
                          variant={isListening ? "destructive" : "secondary"}
                          onClick={() =>
                            isListening ? stopListening(true) : startListening()
                          }
                          disabled={parseDraft.isPending}
                          className="h-8 sm:h-9 text-xs sm:text-sm"
                        >
                          {isListening ? (
                            <>
                              <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                              Done & Prefill
                            </>
                          ) : (
                            <>
                              <Mic className="mr-1.5 h-3.5 w-3.5" />
                              Start Voice Input
                            </>
                          )}
                        </Button>

                        <p className="text-[10px] sm:text-xs text-muted-foreground">
                          Active locale: {resolvedSpeechLocale}
                          {speechSupported ? "" : " | Browser voice capture unavailable"}
                        </p>
                      </div>

                      <p className="text-[10px] sm:text-xs text-muted-foreground">
                        Medicine matching still depends on names saved in inventory. Always review the prefilled fields before saving.
                      </p>
                    </div>
                  )}

                  <Textarea
                    placeholder={
                      speechModeEnabled
                        ? "Speak or edit the transcript here before prefilling."
                        : "Example: Paracetamol 650 mg twice a day morning and night after meal for 5 days. Instructions: drink water."
                    }
                    value={draftInput}
                    onChange={(e) => setDraftInput(e.target.value.slice(0, textModeMaxChars))}
                    rows={5}
                    className="text-xs sm:text-sm"
                  />

                  <div className="flex items-center justify-between gap-3">
                    <p className="text-[10px] sm:text-xs text-muted-foreground">
                      {draftInput.length}/{textModeMaxChars} characters
                    </p>
                    <Button
                      type="button"
                      variant="secondary"
                      onClick={() => handleParseDraft()}
                      disabled={!draftInput.trim() || parseDraft.isPending || isListening}
                      className="h-8 sm:h-9 text-xs sm:text-sm"
                    >
                      {parseDraft.isPending
                        ? "Prefilling..."
                        : speechModeEnabled
                          ? "Prefill From Transcript"
                          : "Prefill From Text"}
                    </Button>
                  </div>

                  {speechError && (
                    <div className="rounded-md bg-red-50 p-3 text-[11px] sm:text-sm text-red-700">
                      {speechError}
                    </div>
                  )}

                  {(draftWarnings.length > 0 || draftMissingFields.length > 0) && (
                    <div className="rounded-md bg-amber-50 p-3 text-[11px] sm:text-sm text-amber-900">
                      {draftWarnings.length > 0 && (
                        <ul className="list-disc space-y-1 pl-4">
                          {draftWarnings.map((warning) => (
                            <li key={warning}>{warning}</li>
                          ))}
                        </ul>
                      )}
                      {draftMissingFields.length > 0 && (
                        <p className="mt-2">
                          Missing fields: {draftMissingFields.join(", ")}.
                        </p>
                      )}
                    </div>
                  )}
                </div>
              )}

              {/* Medicine search / selected medicine */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">Medicine</Label>

                {selectedMedicineName ? (
                  <div className="flex items-center justify-between rounded-lg border bg-muted/40 px-2 sm:px-3 py-1.5 sm:py-2">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-xs sm:text-sm">
                        {selectedMedicineName}
                      </span>
                    </div>
                    <button
                      type="button"
                      onClick={clearSelectedMedicine}
                      className="rounded-md p-1 text-muted-foreground hover:bg-muted"
                    >
                      <X className="h-3 w-3 sm:h-4 sm:w-4" />
                    </button>
                  </div>
                ) : (
                  <div className="space-y-2">
                    <Input
                      placeholder="Search medicine..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="h-9 sm:h-10 text-xs sm:text-sm"
                    />

                    {!!searchQuery && (
                      <div className="max-h-48 sm:max-h-56 overflow-y-auto rounded-md border bg-background">
                        <button
                          type="button"
                          onClick={() => {
                            setValue("medicine_id", "custom", { shouldValidate: true });
                            setValue("medicine_name", searchQuery, { shouldValidate: true });
                            setSearchQuery("");
                          }}
                          className="flex w-full items-center justify-between px-2 sm:px-3 py-1.5 sm:py-2 text-left hover:bg-muted border-b text-primary font-medium"
                        >
                          <span className="text-xs sm:text-sm">Use "{searchQuery}" as custom medicine</span>
                        </button>
                        {medicinesQuery.isLoading ? (
                          <div className="p-2 sm:p-3 text-xs sm:text-sm text-muted-foreground">
                            Searching...
                          </div>
                        ) : medicineList.length > 0 ? (
                          medicineList.map((medicine: MedicineItem) => (
                            <button
                              key={medicine.id}
                              type="button"
                              onClick={() => handleSelectMedicine(medicine)}
                              className="flex w-full items-center justify-between px-2 sm:px-3 py-1.5 sm:py-2 text-left hover:bg-muted"
                            >
                              <span className="text-xs sm:text-sm">{medicine.name}</span>
                              <span className="text-[9px] sm:text-xs text-muted-foreground">
                                {medicine.type || "tablet"}
                              </span>
                            </button>
                          ))
                        ) : (
                          <div className="p-2 sm:p-3 text-xs sm:text-sm text-muted-foreground">
                            No medicines found
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                )}

                {errors.medicine_name && (
                  <p className="text-[11px] sm:text-sm text-red-500">
                    {errors.medicine_name.message}
                  </p>
                )}
              </div>

              {/* Dosage */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">
                  Dosage{medicationType ? ` (${medicationType})` : ""} *
                </Label>
                <Select
                  value={watch("dosage")}
                  onValueChange={(value) =>
                    setValue("dosage", value, { shouldValidate: true })
                  }
                >
                  <SelectTrigger className="h-9 sm:h-10 text-xs sm:text-sm">
                    <SelectValue placeholder="Select dosage" />
                  </SelectTrigger>
                  <SelectContent>
                    {dosageOptions.map((item) => (
                      <SelectItem key={item.value} value={item.value} className="text-xs sm:text-sm">
                        {item.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.dosage && (
                  <p className="text-[11px] sm:text-sm text-red-500">{errors.dosage.message}</p>
                )}
              </div>

              {/* Frequency */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">Frequency *</Label>
                <Select
                  value={watch("frequency")}
                  onValueChange={(value) =>
                    setValue("frequency", value, { shouldValidate: true })
                  }
                >
                  <SelectTrigger className="h-9 sm:h-10 text-xs sm:text-sm">
                    <SelectValue placeholder="Select frequency" />
                  </SelectTrigger>
                  <SelectContent>
                    {frequencyOptions.map((item) => (
                      <SelectItem key={item.value} value={item.value} className="text-xs sm:text-sm">
                        {item.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.frequency && (
                  <p className="text-[11px] sm:text-sm text-red-500">
                    {errors.frequency.message}
                  </p>
                )}
              </div>

              {/* Timings */}
              <div className="space-y-2 sm:space-y-3">
                <Label className="text-xs sm:text-sm">Timings</Label>
                <div className="grid grid-cols-2 gap-2 sm:gap-3">
                  <CheckboxField
                    label="Morning"
                    checked={!!watch("timing_morning")}
                    onCheckedChange={(checked) =>
                      setValue("timing_morning", !!checked)
                    }
                  />
                  <CheckboxField
                    label="Afternoon"
                    checked={!!watch("timing_afternoon")}
                    onCheckedChange={(checked) =>
                      setValue("timing_afternoon", !!checked)
                    }
                  />
                  <CheckboxField
                    label="Evening"
                    checked={!!watch("timing_evening")}
                    onCheckedChange={(checked) =>
                      setValue("timing_evening", !!checked)
                    }
                  />
                  <CheckboxField
                    label="Night"
                    checked={!!watch("timing_night")}
                    onCheckedChange={(checked) =>
                      setValue("timing_night", !!checked)
                    }
                  />
                </div>
              </div>

              {/* Meal */}
              <RadioField
                label="Meal *"
                value={meal || ""}
                onChange={(value) =>
                  setValue("meal", value as PrescriptionForm["meal"], {
                    shouldValidate: true,
                  })
                }
                options={mealOptions}
                direction="row"
                error={errors.meal?.message}
              />

              {/* Dates */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div className="space-y-1.5 sm:space-y-2">
                  <Label className="text-xs sm:text-sm">Start Date</Label>
                  <Input
                    type="date"
                    min={getTodayDate()}
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    className="h-9 sm:h-10 text-xs sm:text-sm"
                  />
                </div>

                <div className="space-y-1.5 sm:space-y-2">
                  <Label className="text-xs sm:text-sm">End Date</Label>
                  <Input
                    type="date"
                    min={startDate || getTodayDate()}
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                    className="h-9 sm:h-10 text-xs sm:text-sm"
                  />
                </div>
              </div>

              {/* Stamp preference */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">Stamp Preference *</Label>
                <Select
                  value={watch("stamp_preference")}
                  onValueChange={(value) =>
                    setValue("stamp_preference", value, {
                      shouldValidate: true,
                    })
                  }
                >
                  <SelectTrigger className="h-9 sm:h-10 text-xs sm:text-sm">
                    <SelectValue placeholder="Select stamp preference" />
                  </SelectTrigger>
                  <SelectContent>
                    {stampOptions.map((item) => (
                      <SelectItem key={item.value} value={item.value} className="text-xs sm:text-sm">
                        {item.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.stamp_preference && (
                  <p className="text-[11px] sm:text-sm text-red-500">
                    {errors.stamp_preference.message}
                  </p>
                )}
              </div>

              {/* Notes */}
              <div className="space-y-1.5 sm:space-y-2">
                <Label className="text-xs sm:text-sm">Notes</Label>
                <Textarea
                  placeholder="Write instructions..."
                  value={watch("instructions") || ""}
                  onChange={(e) =>
                    setValue("instructions", e.target.value, {
                      shouldValidate: true,
                    })
                  }
                  rows={4}
                  className="text-xs sm:text-sm"
                />
              </div>

              <Button
                type="submit"
                className="w-full h-9 sm:h-10 text-xs sm:text-sm"
                disabled={addPrescription.isPending}
              >
                {addPrescription.isPending ? "Saving..." : "Add Prescription"}
              </Button>
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
    <div className="flex items-center space-x-2 rounded-md border p-2 sm:p-3">
      <Checkbox checked={checked} onCheckedChange={onCheckedChange} className="h-3 w-3 sm:h-4 sm:w-4" />
      <Label className="text-[10px] sm:text-sm">{label}</Label>
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
          <DialogTitle className="text-base sm:text-lg">Prescription Added</DialogTitle>
        </DialogHeader>
        <p className="text-xs sm:text-sm text-muted-foreground">
          The prescription has been added successfully.
        </p>
        <Button onClick={onClose} className="mt-4 w-full h-9 sm:h-10 text-xs sm:text-sm">
          OK
        </Button>
      </DialogContent>
    </Dialog>
  );
}

function getTodayDate() {
  return new Date().toISOString().split("T")[0];
}

function getDosageOptions(type: string) {
  const t = (type || "").toLowerCase();

  if (t.includes("tablet") || t.includes("capsule")) {
    return [
      { label: "½ Tablet", value: "0.5 tablet" },
      { label: "1 Tablet", value: "1 tablet" },
      { label: "1½ Tablets", value: "1.5 tablets" },
      { label: "2 Tablets", value: "2 tablets" },
      { label: "3 Tablets", value: "3 tablets" },
    ];
  }

  if (
    t.includes("liquid") ||
    t.includes("syrup") ||
    t.includes("suspension") ||
    t.includes("solution")
  ) {
    return [
      { label: "2.5 ml (½ spoon)", value: "2.5 ml" },
      { label: "5 ml (1 spoon)", value: "5 ml" },
      { label: "10 ml (2 spoons)", value: "10 ml" },
      { label: "15 ml (3 spoons)", value: "15 ml" },
      { label: "20 ml (4 spoons)", value: "20 ml" },
    ];
  }

  if (t.includes("drop")) {
    return [
      { label: "1 Drop", value: "1 drop" },
      { label: "2 Drops", value: "2 drops" },
      { label: "3 Drops", value: "3 drops" },
      { label: "4 Drops", value: "4 drops" },
    ];
  }

  if (t.includes("cream") || t.includes("ointment") || t.includes("gel")) {
    return [
      { label: "Thin layer", value: "thin layer" },
      { label: "Pea-sized amount", value: "pea-sized amount" },
      { label: "As prescribed", value: "as prescribed" },
    ];
  }

  return [
    { label: "1 Unit", value: "1 unit" },
    { label: "2 Units", value: "2 units" },
    { label: "As prescribed", value: "as prescribed" },
  ];
}
