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
import { getMedicines } from "@/api/medicines";

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
  source_type?: "text" | "speech";
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

type AddedMedicine = {
  medicine_id?: string | null;
  medicine_name: string;
  medication_type: string;
  dosage: string;
  frequency: string;
  timing_morning: boolean;
  timing_afternoon: boolean;
  timing_evening: boolean;
  timing_night: boolean;
  meal: PrescriptionForm["meal"];
  instructions?: string;
  start_date?: string | null;
  end_date?: string | null;
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

  const [entryMode, setEntryMode] = useState<EntryMode>(
    getDefaultEntryMode(dictationEnabled)
  );
  const [guidedStep, setGuidedStep] = useState(1);
  const [searchQuery, setSearchQuery] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [startDate, setStartDate] = useState<string>(getTodayDate());
  const [endDate, setEndDate] = useState<string>("");
  const [showSuccess, setShowSuccess] = useState(false);
  const [guidedTranscripts, setGuidedTranscripts] = useState<
    Record<number, string>
  >(createEmptyGuidedTranscripts());
  const [draftId, setDraftId] = useState<string | null>(null);
  const [draftWarnings, setDraftWarnings] = useState<string[]>([]);
  const [draftMissingFields, setDraftMissingFields] = useState<string[]>([]);
  const [isListening, setIsListening] = useState(false);
  const [speechError, setSpeechError] = useState<string | null>(null);
  const [selectedSpeechLocale, setSelectedSpeechLocale] = useState<VoiceLocale>(
    () => getInitialVoiceLocale(assistantConfig?.speech_locale)
  );

  const [addedMedicines, setAddedMedicines] = useState<AddedMedicine[]>([]);
  const [editingIndex, setEditingIndex] = useState<number | null>(null);
  const [selectedMedicineSource, setSelectedMedicineSource] = useState<"inventory" | "doctor_added" | "custom" | null>(null);

  const [voiceDraftMedicine, setVoiceDraftMedicine] = useState<DraftFormPayload | null>(null);
  const [missingFieldsList, setMissingFieldsList] = useState<string[]>([]);

  const [isSearchingMedicine, setIsSearchingMedicine] = useState(false);
  const [showCustomConfirm, setShowCustomConfirm] = useState<{ name: string } | null>(null);

  const recognitionRef = useRef<BrowserSpeechRecognition | null>(null);
  const shouldParseAfterStopRef = useRef(false);
  const draftInputRef = useRef("");
  const hasSpeechInputRef = useRef(false);
  const transcriptBaseRef = useRef("");
  const transcriptFinalRef = useRef("");
  const guidedTranscriptsRef = useRef<Record<number, string>>(
    createEmptyGuidedTranscripts()
  );

  const setDraftInput = (value: string) => {
    draftInputRef.current = value;
  };

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
    getValues,
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

  const lookupSearchTerm = debouncedSearch || "";
  const medicinesQuery = useMedicines({
    page: 1,
    per_page: 20,
    search: lookupSearchTerm,
    include_doctor_added: true,
  });
  const medicineList = useMemo(
    () => medicinesQuery.data?.data || [],
    [medicinesQuery.data?.data]
  );
  const speechSupported =
    typeof window !== "undefined" &&
    Boolean(window.SpeechRecognition || window.webkitSpeechRecognition);

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

    // Stop listening
    shouldParseAfterStopRef.current = false;
    recognitionRef.current?.abort();
    recognitionRef.current = null;
    setIsListening(false);

    // Reset refs
    draftInputRef.current = "";
    hasSpeechInputRef.current = false;
    guidedTranscriptsRef.current = createEmptyGuidedTranscripts();

    // Reset states
    reset(defaultFormValues);
    setDraftInput("");
    setDraftId(null);
    setDraftWarnings([]);
    setDraftMissingFields([]);
    setSearchQuery("");
    setShowSuccess(false);
    setEntryMode(getDefaultEntryMode(dictationEnabled));
    setGuidedStep(1);
    setGuidedTranscripts(createEmptyGuidedTranscripts());
    setSpeechError(null);
    setSelectedSpeechLocale(getInitialVoiceLocale(assistantConfig?.speech_locale));
    setStartDate(getTodayDate());
    setEndDate("");
    setAddedMedicines([]);
    setEditingIndex(null);
    setSelectedMedicineSource(null);
    setVoiceDraftMedicine(null);
    setMissingFieldsList([]);
    setIsSearchingMedicine(false);
    setShowCustomConfirm(null);
  }, [open, dictationEnabled, assistantConfig?.speech_locale, reset]);

  useEffect(() => {
    if (!dictationEnabled && entryMode === "voice") {
      setEntryMode("manual");
    }
  }, [dictationEnabled, entryMode]);

  const dosageOptions = useMemo(() => {
    return getDosageOptions(medicationType);
  }, [medicationType]);

  const medicineStatus = useMemo(() => {
    if (!selectedMedicineName) {
      return null;
    }

    if (selectedMedicineSource === "inventory") {
      return {
        tone: "green" as const,
        title: "Found in medicine inventory",
        description: "This medicine exists in the main medicine database.",
      };
    }

    if (selectedMedicineSource === "doctor_added") {
      return {
        tone: "amber" as const,
        title: "Found in doctor-added medicines",
        description: "This custom medicine already exists and will be reused.",
      };
    }

    return {
      tone: "blue" as const,
      title: "New custom medicine",
      description: "This name is not in the database. It will be saved as a custom medicine.",
    };
  }, [selectedMedicineSource, selectedMedicineName]);

  const handleSelectMedicine = (medicine: MedicineItem) => {
    setValue(
      "medicine_id",
      medicine.source === "inventory" ? medicine.id : "",
      {
        shouldValidate: true,
      }
    );
    setValue("medicine_name", medicine.name, { shouldValidate: true });
    setValue("medication_type", medicine.type || medicationType || "tablet", {
      shouldValidate: true,
    });
    setSelectedMedicineSource(medicine.source || "custom");
    setSearchQuery("");
  };

  const handleUseCustomMedicine = (medicineName: string) => {
    const value = medicineName.trim();

    if (!value) {
      return;
    }

    setValue("medicine_id", "", { shouldValidate: true });
    setValue("medicine_name", value, { shouldValidate: true });
    setSelectedMedicineSource("custom");
    setSearchQuery("");
  };

  const clearSelectedMedicine = () => {
    setValue("medicine_id", "", { shouldValidate: true });
    setValue("medicine_name", "", { shouldValidate: true });
    setSelectedMedicineSource(null);
    setSearchQuery("");
  };

  const handleStep1Complete = async (text: string) => {
    const trimmed = text.trim();
    if (!trimmed) return;

    setIsSearchingMedicine(true);
    setShowCustomConfirm(null);
    try {
      const response = await getMedicines({
        page: 1,
        per_page: 20,
        search: trimmed,
        include_doctor_added: true,
      });

      const list = response?.data || [];
      const matched = list.find(
        (m: MedicineItem) => m.name.toLowerCase() === trimmed.toLowerCase()
      );

      if (matched) {
        handleSelectMedicine(matched);
        setGuidedStep(2);
      } else {
        setShowCustomConfirm({ name: trimmed });
      }
    } catch (error) {
      setShowCustomConfirm({ name: trimmed });
    } finally {
      setIsSearchingMedicine(false);
    }
  };

  const handleAddOrUpdateMedicine = async () => {
    const fieldsToValidate = voiceDraftMedicine !== null
      ? (missingFieldsList as Array<keyof PrescriptionForm>)
      : ["medicine_name", "medication_type", "dosage", "frequency", "meal"] as Array<keyof PrescriptionForm>;

    const isValid = await trigger(fieldsToValidate);

    if (!isValid) {
      return;
    }

    const medicineName = getValues("medicine_name");
    const medicineId = getValues("medicine_id");
    const medicationType = getValues("medication_type");
    const dosage = getValues("dosage");
    const frequency = getValues("frequency");
    const timingMorning = getValues("timing_morning");
    const timingAfternoon = getValues("timing_afternoon");
    const timingEvening = getValues("timing_evening");
    const timingNight = getValues("timing_night");
    const meal = getValues("meal");
    const instructions = getValues("instructions");

    const newMedicine: AddedMedicine = {
      medicine_id: medicineId || null,
      medicine_name: medicineName.trim(),
      medication_type: medicationType,
      dosage: dosage,
      frequency: frequency,
      timing_morning: !!timingMorning,
      timing_afternoon: !!timingAfternoon,
      timing_evening: !!timingEvening,
      timing_night: !!timingNight,
      meal: meal,
      instructions: instructions || "",
      start_date: startDate || null,
      end_date: endDate || null,
    };

    if (editingIndex !== null) {
      setAddedMedicines((prev) => {
        const updated = [...prev];
        updated[editingIndex] = newMedicine;
        return updated;
      });
      setEditingIndex(null);
    } else {
      setAddedMedicines((prev) => [...prev, newMedicine]);
    }

    // Reset voice draft completion state
    setVoiceDraftMedicine(null);
    setMissingFieldsList([]);

    const currentStamp = getValues("stamp_preference");
    reset({
      ...defaultFormValues,
      stamp_preference: currentStamp,
    });
    setStartDate(getTodayDate());
    setEndDate("");
    setSearchQuery("");
    setSelectedMedicineSource(null);

    // Reset transcripts and refs for next voice input to be completely empty
    setGuidedTranscripts(createEmptyGuidedTranscripts());
    guidedTranscriptsRef.current = createEmptyGuidedTranscripts();
    setGuidedStep(1);
    draftInputRef.current = "";
    hasSpeechInputRef.current = false;
  };

  const handleCancelEdit = () => {
    setEditingIndex(null);
    const currentStamp = getValues("stamp_preference");
    reset({
      ...defaultFormValues,
      stamp_preference: currentStamp,
    });
    setStartDate(getTodayDate());
    setEndDate("");
    setSearchQuery("");
    setSelectedMedicineSource(null);
  };

  const handleEditMedicine = (index: number) => {
    const med = addedMedicines[index];
    if (!med) return;

    setEditingIndex(index);

    setValue("medicine_id", med.medicine_id || "");
    setValue("medicine_name", med.medicine_name);
    setValue("medication_type", med.medication_type);
    setValue("dosage", med.dosage);
    setValue("frequency", med.frequency);
    setValue("timing_morning", med.timing_morning);
    setValue("timing_afternoon", med.timing_afternoon);
    setValue("timing_evening", med.timing_evening);
    setValue("timing_night", med.timing_night);
    setValue("meal", med.meal);
    setValue("instructions", med.instructions || "");

    setStartDate(med.start_date || getTodayDate());
    setEndDate(med.end_date || "");

    if (med.medicine_id) {
      setSelectedMedicineSource("inventory");
    } else {
      setSelectedMedicineSource("custom");
    }

    setVoiceDraftMedicine(null);
    setMissingFieldsList([]);
    // Stay in the current entryMode (Voice or Manual)
  };

  const handleDeleteMedicine = (index: number) => {
    setAddedMedicines((prev) => prev.filter((_, i) => i !== index));
    if (editingIndex === index) {
      handleCancelEdit();
    } else if (editingIndex !== null && editingIndex > index) {
      setEditingIndex(editingIndex - 1);
    }
  };

  const handleFinalSubmit = async () => {
    if (addedMedicines.length === 0) {
      alert("Please add at least one medicine to the prescription list.");
      return;
    }

    const isStampValid = await trigger("stamp_preference");
    if (!isStampValid) {
      return;
    }

    const stampPref = getValues("stamp_preference");

    const medicinesPayload = addedMedicines.map((med) => {
      const timings: string[] = [];
      if (med.timing_morning) timings.push("morning");
      if (med.timing_afternoon) timings.push("afternoon");
      if (med.timing_evening) timings.push("evening");
      if (med.timing_night) timings.push("night");

      return {
        medicine_id: med.medicine_id || null,
        medicine_name: med.medicine_name.trim(),
        medication_type: med.medication_type,
        dosage: med.dosage,
        frequency: med.frequency,
        timings,
        meal: med.meal,
        start_date: med.start_date || null,
        end_date: med.end_date || null,
        instructions: med.instructions || "",
      };
    });

    const payload = {
      draft_id: draftId,
      stamp_preference: stampPref,
      medicines: medicinesPayload,
    };

    addPrescription.mutate(payload, {
      onSuccess: () => {
        setShowSuccess(true);
      },
      onError: (error: RequestError) => {
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
    onOpenChange(false);
  };

  const applyDraftToForm = (payload: DraftResponsePayload) => {
    const form = payload?.form || {};

    const normType = normalizeMedicationType(form.medication_type);
    const normDosage = normalizeDosage(form.dosage, normType);
    const normFreq = normalizeFrequency(form.frequency);
    const normMeal = normalizeMealRelation(form.meal);

    setValue("medicine_id", form.medicine_id || "", { shouldValidate: true });
    setValue("medicine_name", form.medicine_name || "", {
      shouldValidate: true,
    });
    setValue("medication_type", normType, {
      shouldValidate: true,
    });
    setValue("dosage", normDosage, { shouldValidate: true });
    setValue("frequency", normFreq, { shouldValidate: true });

    let tm = Boolean(form.timing_morning);
    let ta = Boolean(form.timing_afternoon);
    let te = Boolean(form.timing_evening);
    let tn = Boolean(form.timing_night);

    // If all timings are false but we have a frequency, set default timings based on frequency
    if (!tm && !ta && !te && !tn && normFreq) {
      if (normFreq === "OD") {
        tn = true;
      } else if (normFreq === "BD") {
        tm = true;
        tn = true;
      } else if (normFreq === "TDS") {
        tm = true;
        ta = true;
        tn = true;
      }
    }

    setValue("timing_morning", tm);
    setValue("timing_afternoon", ta);
    setValue("timing_evening", te);
    setValue("timing_night", tn);
    setValue("meal", normMeal, { shouldValidate: true });

    setValue("instructions", form.instructions || "", { shouldValidate: true });
    setValue("stamp_preference", form.stamp_preference || "only_global", {
      shouldValidate: true,
    });

    setStartDate(form.start_date || getTodayDate());
    setEndDate(form.end_date || "");
    setSearchQuery("");

    if (form.medicine_id) {
      setSelectedMedicineSource("inventory");
    } else if (form.medicine_name) {
      setSelectedMedicineSource("custom");
    }
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
        source_type: hasSpeechInputRef.current ? "speech" : "text",
      },
      {
        onSuccess: (response: { data?: DraftResponsePayload }) => {
          const payload = response?.data || {};
          setDraftId(payload.draft_id || null);
          setDraftWarnings(payload.warnings || []);
          setDraftMissingFields(payload.missing_fields || []);
          applyDraftToForm(payload);

          const form = payload?.form || {};

          const normType = normalizeMedicationType(form.medication_type);
          const normDosage = normalizeDosage(form.dosage, normType);
          const normFreq = normalizeFrequency(form.frequency);
          const normMeal = normalizeMealRelation(form.meal);

          const missing: string[] = [];
          if (!form.medicine_name || form.medicine_name.trim().length < 2) {
            missing.push("medicine_name");
          }
          if (!normType) {
            missing.push("medication_type");
          }
          if (!normDosage) {
            missing.push("dosage");
          }
          if (!normFreq) {
            missing.push("frequency");
          }
          if (!normMeal) {
            missing.push("meal");
          }

          if (missing.length === 0) {
            // Apply timings to the new medicine based on current form inputs (which are normalized/defaulted)
            const newMedicine: AddedMedicine = {
              medicine_id: form.medicine_id || null,
              medicine_name: form.medicine_name!.trim(),
              medication_type: normType,
              dosage: normDosage,
              frequency: normFreq,
              timing_morning: !!getValues("timing_morning"),
              timing_afternoon: !!getValues("timing_afternoon"),
              timing_evening: !!getValues("timing_evening"),
              timing_night: !!getValues("timing_night"),
              meal: normMeal,
              instructions: form.instructions || "",
              start_date: form.start_date || getTodayDate(),
              end_date: form.end_date || null,
            };
            setAddedMedicines((prev) => [...prev, newMedicine]);

            const currentStamp = getValues("stamp_preference");
            reset({
              ...defaultFormValues,
              stamp_preference: currentStamp,
            });
            setStartDate(getTodayDate());
            setEndDate("");
            setSearchQuery("");
            setSelectedMedicineSource(null);

            setGuidedTranscripts(createEmptyGuidedTranscripts());
            guidedTranscriptsRef.current = createEmptyGuidedTranscripts();
            setGuidedStep(1);
            draftInputRef.current = "";
            hasSpeechInputRef.current = false;
          } else {
            setVoiceDraftMedicine(form);
            setMissingFieldsList(missing);
          }
        },
        onError: (error: RequestError) => {
          alert(
            error?.response?.data?.errors?.message ||
            error?.message ||
            "Failed to parse prescription text."
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
        const combined = buildGuidedDraftText();
        if (combined) {
          handleParseDraft(combined);
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
    hasSpeechInputRef.current = true;
    transcriptBaseRef.current = (
      guidedTranscriptsRef.current[guidedStep] || ""
    ).trim();

    transcriptFinalRef.current = "";
    shouldParseAfterStopRef.current = false;

    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = selectedSpeechLocale;
    recognition.maxAlternatives = 1;

    recognition.onresult = (event) => {
      let finalTranscript = "";
      let interimTranscript = "";

      for (let index = 0; index < event.results.length; index += 1) {
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
        interimTranscript
      );

      setGuidedTranscripts((prev) => {
        const updated = {
          ...prev,
          [guidedStep]: nextText,
        };
        guidedTranscriptsRef.current = updated;
        return updated;
      });

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

      if (shouldPrefill) {
        const combined = buildGuidedDraftText();
        if (combined) {
          handleParseDraft(combined);
        }
      } else {
        const currentTranscript = (guidedTranscriptsRef.current[guidedStep] || "").trim();
        if (currentTranscript) {
          if (guidedStep === 1) {
            handleStep1Complete(currentTranscript);
          } else if (guidedStep < 4) {
            setGuidedStep((prev) => prev + 1);
          } else if (guidedStep === 4) {
            handleFinishGuidedPrefill();
          }
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

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent className="w-[95vw] max-w-4xl! rounded-2xl p-0 overflow-hidden flex flex-col max-h-[90vh]">
          <DialogHeader className="border-b px-5 py-4 pr-14 sm:pr-20 shrink-0">
            <div className="flex items-start justify-between gap-3">
              <div className="space-y-1">
                <DialogTitle className="text-lg sm:text-xl font-bold">
                  Add Prescription
                </DialogTitle>
                <p className="text-xs text-muted-foreground">
                  {entryMode === "voice"
                    ? "Dictate medicine details. Unknown medicine names will be saved as custom medicines."
                    : entryMode === "manual"
                      ? "Fill in medicine details. Unknown medicine names will be saved as custom medicines."
                      : "Choose how you want to add this prescription."}
                </p>
              </div>

              {entryMode !== null && dictationEnabled && (
                <button
                  type="button"
                  onClick={() => {
                    stopListening(false);
                    setVoiceDraftMedicine(null);
                    setMissingFieldsList([]);
                    setEntryMode(null);
                  }}
                  className="mr-8 flex items-center gap-1.5 text-primary font-semibold text-[11px] sm:mr-10 sm:text-xs uppercase tracking-wide hover:translate-x-1 transition-transform shrink-0"
                >
                  <Undo2 className="h-3.5 w-3.5 shrink-0" />
                  <span className="whitespace-nowrap">Switch Mode</span>
                </button>
              )}
            </div>
          </DialogHeader>

          <div className="flex-1 overflow-y-auto p-5 sm:p-6 min-h-0 bg-muted/5">
            <form onSubmit={(e) => e.preventDefault()} className="h-full">
              {entryMode === null ? (
                /* 1. Select Entry Mode screen */
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
                      onClick={() => setEntryMode("voice")}
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
                      onClick={() => {
                        stopListening(false);
                        setEntryMode("manual");
                      }}
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
              ) : (
                /* 2. Active Mode (Voice or Manual) layout */
                <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">

                  {/* Left Column: Form / Assistant Card */}
                  <div className="md:col-span-7 bg-background border rounded-2xl p-4 sm:p-5 shadow-sm">
                    {entryMode === "voice" ? (
                      editingIndex !== null ? (
                        /* Voice Mode: Prefilled Edit Form */
                        <div className="space-y-4">
                          <div className="font-semibold text-xs text-muted-foreground border-b pb-1.5 flex items-center justify-between">
                            <span>Edit Medicine Details</span>
                            <span className="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 font-semibold uppercase tracking-wider">
                              Editing Item #{editingIndex + 1}
                            </span>
                          </div>

                          {/* Field 1: Medicine Selection */}
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
                                  onClick={clearSelectedMedicine}
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
                                      onClick={() => handleUseCustomMedicine(searchQuery)}
                                      className="flex w-full items-center justify-between border-b px-3 py-2 text-left hover:bg-muted/40 font-semibold text-primary"
                                    >
                                      <span>Use custom: &quot;{searchQuery.trim()}&quot;</span>
                                      <Stethoscope className="h-3.5 w-3.5" />
                                    </button>
                                    {medicinesQuery.isLoading ? (
                                      <div className="px-3 py-2.5 text-muted-foreground text-[11px]">Searching database...</div>
                                    ) : medicineList.length > 0 ? (
                                      medicineList.map((medicine: MedicineItem) => (
                                        <button
                                          key={`${medicine.source}-${medicine.id}`}
                                          type="button"
                                          onClick={() => handleSelectMedicine(medicine)}
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

                          {/* Status Card */}
                          {medicineStatus && (
                            <MedicineStatusCard
                              tone={medicineStatus.tone}
                              title={medicineStatus.title}
                              description={medicineStatus.description}
                            />
                          )}

                          {/* Fields 2 & 3: Type & Dosage */}
                          <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                              <Label className="text-xs font-semibold">Medication Type *</Label>
                              <Select
                                value={medicationType}
                                onValueChange={(value) => setValue("medication_type", value, { shouldValidate: true })}
                              >
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

                            <div className="space-y-1">
                              <Label className="text-xs font-semibold">Dosage *</Label>
                              <Select
                                value={dosage}
                                onValueChange={(value) => setValue("dosage", value, { shouldValidate: true })}
                              >
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
                          </div>

                          {/* Fields 4 & 5: Frequency & Meal Relation */}
                          <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                              <Label className="text-xs font-semibold">Frequency *</Label>
                              <Select
                                value={frequency}
                                onValueChange={(value) => setValue("frequency", value, { shouldValidate: true })}
                              >
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

                            <div className="space-y-1">
                              <Label className="text-xs font-semibold">Meal Relation *</Label>
                              <Select
                                value={meal || ""}
                                onValueChange={(value) => setValue("meal", value as PrescriptionForm["meal"], { shouldValidate: true })}
                              >
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
                          </div>

                          {/* Field 6: Timings Tag Grid */}
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
                                  onClick={() => setValue(item.name, !item.val)}
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

                          {/* Fields 7 & 8: Start & End Dates */}
                          <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                              <Label className="text-xs font-semibold">Start Date</Label>
                              <Input
                                type="date"
                                min={getTodayDate()}
                                value={startDate}
                                onChange={(event) => setStartDate(event.target.value)}
                                className="h-8.5 text-xs rounded-lg"
                              />
                            </div>

                            <div className="space-y-1">
                              <Label className="text-xs font-semibold">End Date</Label>
                              <Input
                                type="date"
                                min={startDate || getTodayDate()}
                                value={endDate}
                                onChange={(event) => setEndDate(event.target.value)}
                                className="h-8.5 text-xs rounded-lg"
                              />
                            </div>
                          </div>

                          {/* Field 9: Instructions Notes */}
                          <div className="space-y-1">
                            <Label className="text-xs font-semibold">Instructions / Notes</Label>
                            <Textarea
                              rows={2}
                              value={instructions || ""}
                              onChange={(event) => setValue("instructions", event.target.value, { shouldValidate: true })}
                              placeholder="e.g. Take after food with warm water"
                              className="text-xs resize-none rounded-lg"
                            />
                          </div>

                          {/* Actions */}
                          <div className="flex gap-2 pt-2 border-t mt-3">
                            <Button
                              type="button"
                              onClick={handleAddOrUpdateMedicine}
                              className="flex-1 h-9 text-xs rounded-lg font-semibold shadow-sm"
                            >
                              Update Medicine
                            </Button>
                            <Button
                              type="button"
                              variant="outline"
                              onClick={handleCancelEdit}
                              className="h-9 text-xs rounded-lg"
                            >
                              Cancel
                            </Button>
                          </div>
                        </div>
                      ) : voiceDraftMedicine !== null ? (
                        /* Voice: Mini Form for Missing Fields */
                        <div className="space-y-4">
                          <div className="border-b pb-2">
                            <h3 className="font-bold text-sm text-foreground">Complete Missing Details</h3>
                            <p className="text-[11px] text-muted-foreground leading-normal">
                              The AI assistant parsed your dictation but needs you to clarify the following fields.
                            </p>
                          </div>

                          {/* Dictation Summary */}
                          <div className="bg-muted/30 border rounded-xl p-3 text-[11px] space-y-1.5">
                            <p className="font-semibold text-muted-foreground uppercase tracking-wider text-[9px]">Captured Details</p>
                            <div className="grid grid-cols-2 gap-2 text-xs">
                              {voiceDraftMedicine.medicine_name && (
                                <div>
                                  <span className="font-medium text-muted-foreground">Medicine:</span> {voiceDraftMedicine.medicine_name}
                                </div>
                              )}
                              {voiceDraftMedicine.medication_type && (
                                <div>
                                  <span className="font-medium text-muted-foreground">Type:</span> {voiceDraftMedicine.medication_type}
                                </div>
                              )}
                              {voiceDraftMedicine.dosage && (
                                <div>
                                  <span className="font-medium text-muted-foreground">Dosage:</span> {voiceDraftMedicine.dosage}
                                </div>
                              )}
                              {voiceDraftMedicine.frequency && (
                                <div>
                                  <span className="font-medium text-muted-foreground">Frequency:</span> {voiceDraftMedicine.frequency}
                                </div>
                              )}
                              {voiceDraftMedicine.meal && (
                                <div>
                                  <span className="font-medium text-muted-foreground">Meal:</span> {voiceDraftMedicine.meal?.replace("_", " ")}
                                </div>
                              )}
                            </div>
                          </div>

                          {/* Missing Inputs */}
                          <div className="space-y-3 pt-1">
                            {missingFieldsList.includes("medicine_name") && (
                              <div className="space-y-1 relative">
                                <Label className="text-xs font-semibold">Medicine Name *</Label>
                                {selectedMedicineName ? (
                                  <div className="flex items-center justify-between rounded-lg border bg-muted/20 px-3 py-1.5">
                                    <span className="text-xs font-bold">{selectedMedicineName}</span>
                                    <button type="button" onClick={clearSelectedMedicine} className="text-muted-foreground hover:text-foreground">
                                      <X className="h-3.5 w-3.5" />
                                    </button>
                                  </div>
                                ) : (
                                  <div className="space-y-1">
                                    <div className="relative">
                                      <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                      <Input
                                        placeholder="Search or type custom medicine name..."
                                        value={searchQuery}
                                        onChange={(event) => setSearchQuery(event.target.value)}
                                        className="pl-8 h-8.5 text-xs rounded-lg"
                                      />
                                    </div>
                                    {searchQuery.trim() !== "" && (
                                      <div className="max-h-40 overflow-y-auto rounded-lg border bg-background text-xs shadow-lg absolute z-20 w-full left-0 mt-1">
                                        <button
                                          type="button"
                                          onClick={() => handleUseCustomMedicine(searchQuery)}
                                          className="flex w-full items-center justify-between border-b px-3 py-1.5 text-left hover:bg-muted/40 font-semibold text-primary"
                                        >
                                          <span>Use custom: &quot;{searchQuery.trim()}&quot;</span>
                                          <Stethoscope className="h-3.5 w-3.5" />
                                        </button>
                                        {medicinesQuery.isLoading ? (
                                          <div className="px-3 py-2 text-muted-foreground text-[10px]">Searching...</div>
                                        ) : medicineList.length > 0 ? (
                                          medicineList.map((medicine: MedicineItem) => (
                                            <button
                                              key={`${medicine.source}-${medicine.id}`}
                                              type="button"
                                              onClick={() => handleSelectMedicine(medicine)}
                                              className="flex w-full items-center justify-between px-3 py-1.5 text-left hover:bg-muted/40 font-medium"
                                            >
                                              <span>{medicine.name}</span>
                                            </button>
                                          ))
                                        ) : (
                                          <div className="px-3 py-2 text-muted-foreground text-[10px]">No medicines found</div>
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

                            {missingFieldsList.includes("medication_type") && (
                              <div className="space-y-1">
                                <Label className="text-xs font-semibold">Medication Type *</Label>
                                <Select
                                  value={medicationType}
                                  onValueChange={(value) => setValue("medication_type", value, { shouldValidate: true })}
                                >
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

                            {missingFieldsList.includes("dosage") && (
                              <div className="space-y-1">
                                <Label className="text-xs font-semibold">Dosage *</Label>
                                <Select
                                  value={dosage}
                                  onValueChange={(value) => setValue("dosage", value, { shouldValidate: true })}
                                >
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

                            {missingFieldsList.includes("frequency") && (
                              <div className="space-y-1">
                                <Label className="text-xs font-semibold">Frequency *</Label>
                                <Select
                                  value={frequency}
                                  onValueChange={(value) => setValue("frequency", value, { shouldValidate: true })}
                                >
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

                            {missingFieldsList.includes("meal") && (
                              <div className="space-y-1">
                                <Label className="text-xs font-semibold">Meal Relation *</Label>
                                <Select
                                  value={meal || ""}
                                  onValueChange={(value) => setValue("meal", value as PrescriptionForm["meal"], { shouldValidate: true })}
                                >
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

                          {/* Mini Form Actions */}
                          <div className="flex gap-2 pt-2 border-t mt-3">
                            <Button type="button" onClick={handleAddOrUpdateMedicine} className="flex-1 h-9 text-xs rounded-lg font-semibold shadow-sm">
                              Confirm & Add
                            </Button>
                            <Button
                              type="button"
                              variant="outline"
                              onClick={() => {
                                setVoiceDraftMedicine(null);
                                setMissingFieldsList([]);
                                const currentStamp = getValues("stamp_preference");
                                reset({ ...defaultFormValues, stamp_preference: currentStamp });
                                setStartDate(getTodayDate());
                                setEndDate("");
                              }}
                              className="h-9 text-xs rounded-lg text-destructive border-destructive/20 hover:bg-destructive/5"
                            >
                              Discard
                            </Button>
                          </div>
                        </div>
                      ) : (
                        /* Voice assistant recording panel */
                        <div className="space-y-4">
                          <div className="font-semibold text-xs text-muted-foreground border-b pb-1.5 flex items-center justify-between">
                            <span>Voice Dictation Assistant</span>
                            <span className="text-[10px] text-primary bg-primary/5 px-2 py-0.5 rounded border border-primary/20 font-semibold uppercase tracking-wider">
                              Voice Mode
                            </span>
                          </div>

                          <div className="space-y-4 bg-muted/10 border border-muted/50 rounded-xl p-0">
                            {/* Language Selection */}
                            <div className="flex items-center justify-between gap-2">
                              <span className="text-xs text-muted-foreground font-medium">Select Language:</span>
                              <div className="flex items-center gap-1 rounded-lg border bg-background p-0.5">
                                {voiceLanguageOptions.map((option) => (
                                  <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => setSelectedSpeechLocale(option.value)}
                                    className={`rounded-md px-2.5 py-1 text-[10px] font-medium transition ${selectedSpeechLocale === option.value
                                        ? "bg-primary text-primary-foreground"
                                        : "text-muted-foreground hover:text-foreground"
                                      }`}
                                  >
                                    {option.label}
                                  </button>
                                ))}
                              </div>
                            </div>

                            {/* Step selectors */}
                            <div className="grid grid-cols-4 gap-1.5">
                              {guidedVoiceSteps.map((step) => {
                                const isActive = guidedStep === step.id;
                                const isComplete = normalizeValue(guidedTranscripts[step.id]) !== "";

                                return (
                                  <button
                                    key={step.id}
                                    type="button"
                                    onClick={() => {
                                      stopListening(false);
                                      setGuidedStep(step.id);
                                    }}
                                    className={`rounded-lg border p-1.5 text-left transition flex flex-col justify-between h-[52px] ${isActive
                                        ? "border-primary bg-primary/5 text-primary"
                                        : "border-border bg-background text-foreground"
                                      }`}
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

                            {/* Speech active area */}
                            <div className="rounded-xl border bg-background p-3.5 space-y-3 shadow-inner relative">
                              <div className="flex justify-between items-start gap-2">
                                <div className="space-y-0.5">
                                  <p className="text-xs font-semibold text-foreground">
                                    {guidedVoiceSteps[guidedStep - 1]?.title}
                                  </p>
                                  <p className="text-[10px] text-muted-foreground leading-normal font-medium italic">
                                    {guidedVoiceSteps[guidedStep - 1]?.hint}
                                  </p>
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
                                  <Button
                                    type="button"
                                    size="sm"
                                    variant={isListening ? "destructive" : "secondary"}
                                    onClick={() => (isListening ? stopListening(false) : startListening())}
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
                                  <p className="text-[10px] text-red-500">Dictation not supported in browser</p>
                                )}
                              </div>

                              <div className="space-y-1">
                                <Label className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">
                                  Transcription
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
                                  className="text-xs resize-none rounded-lg"
                                  placeholder="Speak now or type custom text here..."
                                  disabled={isListening}
                                />
                              </div>

                              {/* Loading / Searching database spinner */}
                              {isSearchingMedicine && guidedStep === 1 && (
                                <div className="flex items-center gap-2 text-xs text-muted-foreground mt-2 bg-muted/20 px-3 py-1.5 rounded-lg border border-dashed animate-pulse">
                                  <Loader2 className="h-3.5 w-3.5 animate-spin text-primary" />
                                  Searching database...
                                </div>
                              )}

                              {/* Medicine selection visual preview */}
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
                                  <button
                                    type="button"
                                    onClick={clearSelectedMedicine}
                                    className="rounded-md p-1 hover:bg-green-100 text-green-700 transition-colors"
                                  >
                                    <X className="h-3.5 w-3.5" />
                                  </button>
                                </div>
                              )}

                              {/* Prompt asking to use custom medicine if not found */}
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
                                    <Button
                                      type="button"
                                      size="sm"
                                      onClick={() => {
                                        handleUseCustomMedicine(showCustomConfirm.name);
                                        setShowCustomConfirm(null);
                                        setGuidedStep(2);
                                      }}
                                      className="h-7 text-[10px] px-3 font-semibold bg-primary hover:bg-primary/90 text-white rounded-lg shadow-sm"
                                    >
                                      Yes, Add Custom
                                    </Button>
                                    <Button
                                      type="button"
                                      size="sm"
                                      variant="outline"
                                      onClick={() => {
                                        setShowCustomConfirm(null);
                                        setGuidedTranscripts((prev) => {
                                          const updated = { ...prev, 1: "" };
                                          guidedTranscriptsRef.current = updated;
                                          return updated;
                                        });
                                      }}
                                      className="h-7 text-[10px] px-3 font-semibold border-amber-300 text-amber-900 hover:bg-amber-100/50 rounded-lg"
                                    >
                                      No, Record Again
                                    </Button>
                                  </div>
                                </div>
                              )}
                            </div>

                            {/* Voice action buttons */}
                            <div className="flex items-center justify-between gap-3">
                              <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() => {
                                  stopListening(false);
                                  setShowCustomConfirm(null);
                                  setGuidedStep((prev) => Math.max(prev - 1, 1));
                                }}
                                disabled={guidedStep === 1 || isListening}
                                className="h-8 text-xs font-medium"
                              >
                                Back
                              </Button>

                              {guidedStep < guidedVoiceSteps.length ? (
                                <Button
                                  type="button"
                                  size="sm"
                                  onClick={() => {
                                    stopListening(false);
                                    if (guidedStep === 1) {
                                      const text = (guidedTranscripts[1] || "").trim();
                                      if (text) {
                                        handleStep1Complete(text);
                                      } else {
                                        setGuidedStep(2);
                                      }
                                    } else {
                                      setGuidedStep((prev) => Math.min(prev + 1, guidedVoiceSteps.length));
                                    }
                                  }}
                                  disabled={isListening}
                                  className="h-8 text-xs font-medium"
                                >
                                  Next Step
                                </Button>
                              ) : (
                                <Button
                                  type="button"
                                  size="sm"
                                  onClick={handleFinishGuidedPrefill}
                                  disabled={
                                    parseDraft.isPending ||
                                    !Object.values(guidedTranscripts).some((val) => Boolean(val.trim()))
                                  }
                                  className="h-8 text-xs font-medium"
                                >
                                  {parseDraft.isPending ? (
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
                              <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                                {speechError}
                              </div>
                            )}
                          </div>
                        </div>
                      )
                    ) : (
                      /* Manual Entry Mode Form fields */
                      <div className="space-y-4">
                        <div className="font-semibold text-xs text-muted-foreground border-b pb-1.5 flex items-center justify-between">
                          <span>{editingIndex !== null ? "Edit Medicine Details" : "Add Medicine Details"}</span>
                          {editingIndex !== null && (
                            <span className="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 font-semibold uppercase tracking-wider">
                              Editing Item #{editingIndex + 1}
                            </span>
                          )}
                        </div>

                        {/* Field 1: Medicine Selection */}
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
                                onClick={clearSelectedMedicine}
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
                                    onClick={() => handleUseCustomMedicine(searchQuery)}
                                    className="flex w-full items-center justify-between border-b px-3 py-2 text-left hover:bg-muted/40 font-semibold text-primary"
                                  >
                                    <span>Use custom: &quot;{searchQuery.trim()}&quot;</span>
                                    <Stethoscope className="h-3.5 w-3.5" />
                                  </button>
                                  {medicinesQuery.isLoading ? (
                                    <div className="px-3 py-2.5 text-muted-foreground text-[11px]">Searching database...</div>
                                  ) : medicineList.length > 0 ? (
                                    medicineList.map((medicine: MedicineItem) => (
                                      <button
                                        key={`${medicine.source}-${medicine.id}`}
                                        type="button"
                                        onClick={() => handleSelectMedicine(medicine)}
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

                        {/* Status Card */}
                        {medicineStatus && (
                          <MedicineStatusCard
                            tone={medicineStatus.tone}
                            title={medicineStatus.title}
                            description={medicineStatus.description}
                          />
                        )}

                        {/* Fields 2 & 3: Type & Dosage */}
                        <div className="grid grid-cols-2 gap-3">
                          <div className="space-y-1">
                            <Label className="text-xs font-semibold">Medication Type *</Label>
                            <Select
                              value={medicationType}
                              onValueChange={(value) => setValue("medication_type", value, { shouldValidate: true })}
                            >
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

                          <div className="space-y-1">
                            <Label className="text-xs font-semibold">Dosage *</Label>
                            <Select
                              value={dosage}
                              onValueChange={(value) => setValue("dosage", value, { shouldValidate: true })}
                            >
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
                        </div>

                        {/* Fields 4 & 5: Frequency & Meal Relation */}
                        <div className="grid grid-cols-2 gap-3">
                          <div className="space-y-1">
                            <Label className="text-xs font-semibold">Frequency *</Label>
                            <Select
                              value={frequency}
                              onValueChange={(value) => setValue("frequency", value, { shouldValidate: true })}
                            >
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

                          <div className="space-y-1">
                            <Label className="text-xs font-semibold">Meal Relation *</Label>
                            <Select
                              value={meal || ""}
                              onValueChange={(value) => setValue("meal", value as PrescriptionForm["meal"], { shouldValidate: true })}
                            >
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
                        </div>

                        {/* Field 6: Timings Tag Grid */}
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
                                onClick={() => setValue(item.name, !item.val)}
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

                        {/* Fields 7 & 8: Start & End Dates */}
                        <div className="grid grid-cols-2 gap-3">
                          <div className="space-y-1">
                            <Label className="text-xs font-semibold">Start Date</Label>
                            <Input
                              type="date"
                              min={getTodayDate()}
                              value={startDate}
                              onChange={(event) => setStartDate(event.target.value)}
                              className="h-8.5 text-xs rounded-lg"
                            />
                          </div>

                          <div className="space-y-1">
                            <Label className="text-xs font-semibold">End Date</Label>
                            <Input
                              type="date"
                              min={startDate || getTodayDate()}
                              value={endDate}
                              onChange={(event) => setEndDate(event.target.value)}
                              className="h-8.5 text-xs rounded-lg"
                            />
                          </div>
                        </div>

                        {/* Field 9: Instructions Notes */}
                        <div className="space-y-1">
                          <Label className="text-xs font-semibold">Instructions / Notes</Label>
                          <Textarea
                            rows={2}
                            value={instructions || ""}
                            onChange={(event) => setValue("instructions", event.target.value, { shouldValidate: true })}
                            placeholder="e.g. Take after food with warm water"
                            className="text-xs resize-none rounded-lg"
                          />
                        </div>

                        {/* Action button to add to local list */}
                        <div className="flex gap-2 pt-2 border-t mt-3">
                          {editingIndex !== null ? (
                            <>
                              <Button
                                type="button"
                                onClick={handleAddOrUpdateMedicine}
                                className="flex-1 h-9 text-xs rounded-lg font-semibold shadow-sm"
                              >
                                Update Medicine
                              </Button>
                              <Button
                                type="button"
                                variant="outline"
                                onClick={handleCancelEdit}
                                className="h-9 text-xs rounded-lg"
                              >
                                Cancel
                              </Button>
                            </>
                          ) : (
                            <Button
                              type="button"
                              onClick={handleAddOrUpdateMedicine}
                              className="w-full h-9 text-xs rounded-lg font-semibold shadow-sm"
                            >
                              + Add to Prescription List
                            </Button>
                          )}
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Right Panel: Prescription List Card */}
                  <div className="md:col-span-5 bg-background border rounded-2xl p-4 sm:p-5 shadow-sm space-y-4 self-start">

                    <div className="font-semibold text-xs text-muted-foreground border-b pb-2 flex items-center justify-between">
                      <span>Prescription Items</span>
                      <span className="text-[10px] font-bold bg-primary/10 text-primary px-2.5 py-0.5 rounded-full border border-primary/20 shadow-sm">
                        {addedMedicines.length} {addedMedicines.length === 1 ? "Item" : "Items"}
                      </span>
                    </div>

                    {/* Empty list state */}
                    {addedMedicines.length === 0 ? (
                      <div className="flex flex-col items-center justify-center py-14 text-center border-2 border-dashed rounded-xl p-4 bg-muted/5">
                        <Stethoscope className="h-10 w-10 text-muted-foreground/50 mb-2 stroke-[1.2]" />
                        <p className="text-xs font-semibold text-muted-foreground">No medicines added</p>
                        <p className="text-[10px] text-muted-foreground/80 mt-1 max-w-[180px] leading-relaxed">
                          Fill and add details using the form on the left to build the prescription.
                        </p>
                      </div>
                    ) : (

                      /* Scrollable list of medicines */
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
                                  <button
                                    type="button"
                                    onClick={() => handleEditMedicine(index)}
                                    className="p-1 hover:bg-muted rounded text-primary transition-colors"
                                    title="Edit medicine"
                                  >
                                    <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                      <path strokeLinecap="round" strokeLinejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                  </button>
                                  <button
                                    type="button"
                                    onClick={() => handleDeleteMedicine(index)}
                                    className="p-1 hover:bg-destructive/10 rounded text-destructive transition-colors"
                                    title="Remove medicine"
                                  >
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

                    {/* Stamp Preference and Final Submit */}
                    <div className="pt-4 border-t space-y-3 bg-background">
                      <div className="space-y-1">
                        <Label className="text-xs font-semibold">Stamp Preference *</Label>
                        <Select
                          value={stampPreference}
                          onValueChange={(value) => setValue("stamp_preference", value, { shouldValidate: true })}
                        >
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
                        {errors.stamp_preference && (
                          <p className="text-[11px] text-red-500 font-medium">{errors.stamp_preference.message}</p>
                        )}
                      </div>

                      <Button
                        type="button"
                        onClick={handleFinalSubmit}
                        disabled={addPrescription.isPending || addedMedicines.length === 0}
                        className="w-full h-10 text-xs sm:text-sm font-semibold rounded-lg shadow-sm"
                      >
                        {addPrescription.isPending ? (
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

function normalizeMedicationType(value?: string | null): string {
  const val = (value || "").trim().toLowerCase();
  if (!val) return "tablet";
  if (val.includes("tablet") || val.includes("tab")) return "tablet";
  if (val.includes("capsule") || val.includes("cap")) return "capsule";
  if (val.includes("syrup") || val.includes("syp") || val.includes("liquid")) return "syrup";
  if (val.includes("drop") || val.includes("eye drop") || val.includes("ear drop")) return "drop";
  if (val.includes("injection") || val.includes("inj")) return "injection";
  if (val.includes("cream")) return "cream";
  if (val.includes("ointment")) return "ointment";
  return "tablet";
}

function normalizeDosage(value?: string | null, type?: string | null): string {
  const val = (value || "").trim().toLowerCase();
  const medType = (type || "").trim().toLowerCase();
  if (!val) return "";

  if (medType.includes("tablet") || medType.includes("capsule")) {
    if (val.includes("0.5") || val.includes("half")) return "0.5 tablet";
    if (val.includes("1.5") || val.includes("one and a half")) return "1.5 tablets";
    if (val.includes("2") || val.includes("two")) return "2 tablets";
    if (val.includes("3") || val.includes("three")) return "3 tablets";
    if (val.includes("1") || val.includes("one")) return "1 tablet";
    return "1 tablet";
  }
  if (medType.includes("syrup") || medType.includes("liquid") || medType.includes("suspension")) {
    if (val.includes("2.5") || val.includes("half spoon")) return "2.5 ml";
    if (val.includes("5") || val.includes("1 spoon") || val.includes("one spoon")) return "5 ml";
    if (val.includes("10") || val.includes("2 spoon") || val.includes("two spoon")) return "10 ml";
    if (val.includes("15") || val.includes("3 spoon") || val.includes("three spoon")) return "15 ml";
    if (val.includes("20") || val.includes("4 spoon") || val.includes("four spoon")) return "20 ml";
    return "5 ml";
  }
  if (medType.includes("drop")) {
    if (val.includes("1") || val.includes("one")) return "1 drop";
    if (val.includes("2") || val.includes("two")) return "2 drops";
    if (val.includes("3") || val.includes("three")) return "3 drops";
    if (val.includes("4") || val.includes("four")) return "4 drops";
    return "1 drop";
  }
  if (medType.includes("cream") || medType.includes("ointment") || medType.includes("gel")) {
    if (val.includes("thin") || val.includes("layer")) return "thin layer";
    if (val.includes("pea") || val.includes("amount")) return "pea-sized amount";
    return "as prescribed";
  }
  if (val.includes("1") || val.includes("one")) return "1 unit";
  if (val.includes("2") || val.includes("two")) return "2 units";
  return "as prescribed";
}

function normalizeFrequency(value?: string | null): string {
  const val = (value || "").trim().toLowerCase();
  if (!val) return "";
  if (val === "od" || val.includes("once a day") || val.includes("once daily") || val.includes("daily") || val === "1") return "OD";
  if (val === "bd" || val.includes("twice a day") || val.includes("twice daily") || val.includes("twice") || val === "2") return "BD";
  if (val === "tds" || val.includes("three times") || val.includes("thrice daily") || val.includes("thrice") || val === "3") return "TDS";
  if (val === "sos" || val.includes("as needed") || val.includes("when needed") || val.includes("whenever")) return "SOS";

  const upper = val.toUpperCase();
  if (["OD", "BD", "TDS", "SOS"].includes(upper)) return upper;

  return "OD";
}

function normalizeMealRelation(value?: string | null): PrescriptionForm["meal"] {
  const val = (value || "").trim().toLowerCase();
  if (!val) return "after_meal";
  if (val.includes("before") || val.includes("empty") || val.includes("ac")) return "before_meal";
  if (val.includes("after") || val.includes("post") || val.includes("pc")) return "after_meal";
  if (val.includes("with") || val.includes("during")) return "with_meal";
  return "after_meal";
}
