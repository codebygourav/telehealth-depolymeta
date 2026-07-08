"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { Undo2, X } from "lucide-react";
import { useParams } from "next/navigation";
import { useEffect, useMemo, useRef, useState } from "react";
import { useForm, useWatch } from "react-hook-form";

import type { VoiceTranscriptionResult } from "@/api/voice-transcription";

import { getMedicines } from "@/api/medicines";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useAuth } from "@/context/userContext";
import { useAddPrescription } from "@/queries/useAddPrescription";
import { useMedicines } from "@/queries/useMedicines";
import { useParsePrescriptionDraft } from "@/queries/useParsePrescriptionDraft";

import PrescriptionEntryModeSelector from "./PrescriptionEntryModeSelector";
import PrescriptionListPanel from "./PrescriptionListPanel";
import PrescriptionMedicineForm from "./PrescriptionMedicineForm";
import PrescriptionSuccessDialog from "./PrescriptionSuccessDialog";
import PrescriptionVoiceAssistantPanel from "./PrescriptionVoiceAssistantPanel";
import {
  PrescriptionSchema,
  type AddedMedicine,
  type EntryMode,
  type MedicineItem,
  type MedicineSource,
  type PrescriptionForm,
  type VoiceLocale,
} from "./prescription-dialog-types";

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
    deepgram_enabled?: boolean;
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

  const deepgramEnabled = Boolean(assistantConfig?.deepgram_enabled);

  const [voiceSubMode, setVoiceSubMode] = useState<"deepgram" | "browser">(
    deepgramEnabled ? "deepgram" : "browser"
  );

  const [entryMode, setEntryMode] = useState<EntryMode>(
    getDefaultEntryMode(dictationEnabled)
  );
  const [guidedStep, setGuidedStep] = useState(1);
  const [searchQuery, setSearchQuery] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [startDate, setStartDate] = useState<string>(getTodayDate());
  const [endDate, setEndDate] = useState<string>("");
  const [showSuccess, setShowSuccess] = useState(false);
  const [guidedTranscripts, setGuidedTranscripts] = useState<Record<number, string>>(
    createEmptyGuidedTranscripts()
  );
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
  const [selectedMedicineSource, setSelectedMedicineSource] = useState<MedicineSource>(null);

  const [voiceDraftMedicine, setVoiceDraftMedicine] = useState<DraftFormPayload | null>(null);
  const [missingFieldsList, setMissingFieldsList] = useState<string[]>([]);

  const [isSearchingMedicine, setIsSearchingMedicine] = useState(false);
  const [showCustomConfirm, setShowCustomConfirm] = useState<{ name: string } | null>(null);

  const [mobileTab, setMobileTab] = useState<"form" | "list">("form");
  const [toastMessage, setToastMessage] = useState<{ text: string; type: "success" | "error" } | null>(null);

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

  useEffect(() => {
    if (toastMessage) {
      const timer = setTimeout(() => setToastMessage(null), 3500);
      return () => clearTimeout(timer);
    }
  }, [toastMessage]);

  const {
    handleSubmit,
    reset,
    setValue,
    getValues,
    control,
    trigger,
    formState: { errors },
  } = useForm<PrescriptionForm>({
    resolver: zodResolver(PrescriptionSchema),
    defaultValues: defaultFormValues,
  });

  const selectedMedicineName = useWatch({ control, name: "medicine_name" }) || "";
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

    recognitionRef.current?.abort();
    recognitionRef.current = null;
    setIsListening(false);

    draftInputRef.current = "";
    hasSpeechInputRef.current = false;
    guidedTranscriptsRef.current = createEmptyGuidedTranscripts();

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
    setMobileTab("form");
    setToastMessage(null);
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
    setValue("medicine_id", medicine.source === "inventory" ? medicine.id : "", {
      shouldValidate: true,
    });
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
    } catch {
      setShowCustomConfirm({ name: trimmed });
    } finally {
      setIsSearchingMedicine(false);
    }
  };

  const handleAddOrUpdateMedicine = async () => {
    const fieldsToValidate = voiceDraftMedicine !== null
      ? (missingFieldsList as Array<keyof PrescriptionForm>)
      : (["medicine_name", "medication_type", "dosage", "frequency", "meal"] as Array<keyof PrescriptionForm>);

    const isValid = await trigger(fieldsToValidate);

    if (!isValid) {
      return;
    }

    const medicineName = getValues("medicine_name");
    const medicineId = getValues("medicine_id");
    const medicationTypeValue = getValues("medication_type");
    const dosageValue = getValues("dosage");
    const frequencyValue = getValues("frequency");
    const timingMorningValue = getValues("timing_morning");
    const timingAfternoonValue = getValues("timing_afternoon");
    const timingEveningValue = getValues("timing_evening");
    const timingNightValue = getValues("timing_night");
    const mealValue = getValues("meal");
    const instructionsValue = getValues("instructions");

    const newMedicine: AddedMedicine = {
      medicine_id: medicineId || null,
      medicine_name: medicineName.trim(),
      medication_type: medicationTypeValue,
      dosage: dosageValue,
      frequency: frequencyValue,
      timing_morning: !!timingMorningValue,
      timing_afternoon: !!timingAfternoonValue,
      timing_evening: !!timingEveningValue,
      timing_night: !!timingNightValue,
      meal: mealValue,
      instructions: instructionsValue || "",
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
      setToastMessage({ text: `Updated ${newMedicine.medicine_name} successfully.`, type: "success" });
    } else {
      setAddedMedicines((prev) => [...prev, newMedicine]);
      setToastMessage({ text: `Added ${newMedicine.medicine_name} to prescription list.`, type: "success" });
    }

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
    setMobileTab("form");
  };

  const handleDeleteMedicine = (index: number) => {
    const med = addedMedicines[index];
    setAddedMedicines((prev) => prev.filter((_, i) => i !== index));
    if (med) {
      setToastMessage({ text: `Removed ${med.medicine_name} from list.`, type: "success" });
    }

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
            setToastMessage({ text: `Added ${newMedicine.medicine_name} to prescription list.`, type: "success" });

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
      }
    );
  };

  const handleDeepgramResult = (result: VoiceTranscriptionResult) => {
    if (result.transcript) {
      setDraftInput(result.transcript);
      handleParseDraft(result.transcript);
    }
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

      const nextText = mergeTranscripts(
        transcriptBaseRef.current,
        finalTranscript,
        interimTranscript
      );

      const cleanedText = cleanDuplicateWords(nextText);

      setGuidedTranscripts((prev) => {
        const updated = {
          ...prev,
          [guidedStep]: cleanedText,
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
        <DialogContent className="w-[95vw] sm:max-w-5xl! rounded-2xl p-0 overflow-hidden flex flex-col gap-0! fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
          {toastMessage && (
            <div className="absolute top-4 left-1/2 -translate-x-1/2 z-100 animate-in fade-in slide-in-from-top-4 duration-300">
              <div className={`flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-semibold shadow-lg border ${toastMessage.type === "success" ? "bg-green-600 text-white border-green-700" : "bg-red-600 text-white border-red-700"}`}>
                <span>{toastMessage.text}</span>
                <button type="button" onClick={() => setToastMessage(null)} className="rounded-full hover:bg-white/10 p-0.5">
                  <X className="h-3 w-3" />
                </button>
              </div>
            </div>
          )}

          <DialogHeader className="border-b px-5 py-4 pr-14 sm:pr-20 shrink-0">
            <div className="flex items-start justify-between gap-3">
              <div className="space-y-1">
                <DialogTitle className="text-lg sm:text-xl font-bold">
                  Add Prescription
                </DialogTitle>
                <DialogDescription className="sr-only">
                  {entryMode === "voice"
                    ? "Dictate medicine details. Unknown medicine names will be saved as custom medicines."
                    : entryMode === "manual"
                      ? "Fill in medicine details. Unknown medicine names will be saved as custom medicines."
                      : "Choose how you want to add this prescription."}
                </DialogDescription>
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
                <PrescriptionEntryModeSelector
                  dictationEnabled={dictationEnabled}
                  onSelectVoice={() => setEntryMode("voice")}
                  onSelectManual={() => {
                    stopListening(false);
                    setEntryMode("manual");
                  }}
                />
              ) : (
                <div className="space-y-4">
                  <div className="flex md:hidden border-b mb-4 bg-muted/20 p-1 rounded-xl">
                    <button
                      type="button"
                      onClick={() => setMobileTab("form")}
                      className={`flex-1 py-2 text-xs font-bold rounded-lg text-center transition-all ${mobileTab === "form" ? "bg-background text-foreground shadow-sm" : "text-muted-foreground"}`}
                    >
                      {entryMode === "voice" ? "Voice Assistant" : "Manual Form"}
                    </button>
                    <button
                      type="button"
                      onClick={() => setMobileTab("list")}
                      className={`flex-1 py-2 text-xs font-bold rounded-lg text-center transition-all relative ${mobileTab === "list" ? "bg-background text-foreground shadow-sm" : "text-muted-foreground"}`}
                    >
                      Prescription List
                      {addedMedicines.length > 0 && (
                        <span className="absolute top-1/2 -translate-y-1/2 right-2.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-primary text-[9px] font-bold text-primary-foreground shadow-sm">
                          {addedMedicines.length}
                        </span>
                      )}
                    </button>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                    <div className={`md:col-span-7 bg-background border rounded-2xl p-4 sm:p-5 shadow-sm ${mobileTab === "form" ? "block" : "hidden md:block"}`}>
                      {entryMode === "voice" ? (
                        editingIndex !== null ? (
                          <PrescriptionMedicineForm
                            title="Edit Medicine Details"
                            editingIndex={editingIndex}
                            selectedMedicineName={selectedMedicineName}
                            selectedMedicineSource={selectedMedicineSource}
                            searchQuery={searchQuery}
                            setSearchQuery={setSearchQuery}
                            medicineList={medicineList}
                            isSearchingMedicine={isSearchingMedicine}
                            medicineStatus={medicineStatus}
                            errors={errors as Record<string, { message?: string } | undefined>}
                            medicationType={medicationType}
                            dosage={dosage}
                            frequency={frequency}
                            meal={meal}
                            timingMorning={timingMorning}
                            timingAfternoon={timingAfternoon}
                            timingEvening={timingEvening}
                            timingNight={timingNight}
                            startDate={startDate}
                            endDate={endDate}
                            instructions={instructions}
                            medicationTypeOptions={medicationTypeOptions}
                            frequencyOptions={frequencyOptions}
                            mealOptions={mealOptions}
                            dosageOptions={dosageOptions}
                            onSelectMedicine={handleSelectMedicine}
                            onUseCustomMedicine={handleUseCustomMedicine}
                            onClearSelectedMedicine={clearSelectedMedicine}
                            onMedicationTypeChange={(value) => setValue("medication_type", value, { shouldValidate: true })}
                            onDosageChange={(value) => setValue("dosage", value, { shouldValidate: true })}
                            onFrequencyChange={(value) => setValue("frequency", value, { shouldValidate: true })}
                            onMealChange={(value) => setValue("meal", value as PrescriptionForm["meal"], { shouldValidate: true })}
                            onTimingChange={(name, value) => setValue(name, value)}
                            onStartDateChange={setStartDate}
                            onEndDateChange={setEndDate}
                            onInstructionsChange={(value) => setValue("instructions", value, { shouldValidate: true })}
                            onSubmit={handleAddOrUpdateMedicine}
                            onCancel={handleCancelEdit}
                            submitLabel="Update Medicine"
                            cancelLabel="Cancel"
                          />
                        ) : voiceDraftMedicine !== null ? (
                          <PrescriptionMedicineForm
                            title="Complete Missing Details"
                            subtitle="The AI assistant parsed your dictation but needs you to clarify the following fields."
                            editingIndex={editingIndex}
                            selectedMedicineName={selectedMedicineName}
                            selectedMedicineSource={selectedMedicineSource}
                            searchQuery={searchQuery}
                            setSearchQuery={setSearchQuery}
                            medicineList={medicineList}
                            isSearchingMedicine={isSearchingMedicine}
                            medicineStatus={medicineStatus}
                            errors={errors as Record<string, { message?: string } | undefined>}
                            medicationType={medicationType}
                            dosage={dosage}
                            frequency={frequency}
                            meal={meal}
                            timingMorning={timingMorning}
                            timingAfternoon={timingAfternoon}
                            timingEvening={timingEvening}
                            timingNight={timingNight}
                            startDate={startDate}
                            endDate={endDate}
                            instructions={instructions}
                            medicationTypeOptions={medicationTypeOptions}
                            frequencyOptions={frequencyOptions}
                            mealOptions={mealOptions}
                            dosageOptions={dosageOptions}
                            visibleFields={missingFieldsList as Array<"medicine_name" | "medication_type" | "dosage" | "frequency" | "meal">}
                            mode="compact"
                            onSelectMedicine={handleSelectMedicine}
                            onUseCustomMedicine={handleUseCustomMedicine}
                            onClearSelectedMedicine={clearSelectedMedicine}
                            onMedicationTypeChange={(value) => setValue("medication_type", value, { shouldValidate: true })}
                            onDosageChange={(value) => setValue("dosage", value, { shouldValidate: true })}
                            onFrequencyChange={(value) => setValue("frequency", value, { shouldValidate: true })}
                            onMealChange={(value) => setValue("meal", value as PrescriptionForm["meal"], { shouldValidate: true })}
                            onTimingChange={(name, value) => setValue(name, value)}
                            onStartDateChange={setStartDate}
                            onEndDateChange={setEndDate}
                            onInstructionsChange={(value) => setValue("instructions", value, { shouldValidate: true })}
                            onSubmit={handleAddOrUpdateMedicine}
                            submitLabel="Confirm & Add"
                            cancelLabel="Discard"
                            onCancel={() => {
                              setVoiceDraftMedicine(null);
                              setMissingFieldsList([]);
                              const currentStamp = getValues("stamp_preference");
                              reset({ ...defaultFormValues, stamp_preference: currentStamp });
                              setStartDate(getTodayDate());
                              setEndDate("");
                            }}
                          />
                        ) : (
                          <PrescriptionVoiceAssistantPanel
                            appointmentId={appointmentId}
                            deepgramEnabled={deepgramEnabled}
                            voiceSubMode={voiceSubMode}
                            setVoiceSubMode={setVoiceSubMode}
                            onDeepgramResult={handleDeepgramResult}
                            voiceLanguageOptions={voiceLanguageOptions}
                            selectedSpeechLocale={selectedSpeechLocale}
                            setSelectedSpeechLocale={setSelectedSpeechLocale}
                            guidedVoiceSteps={guidedVoiceSteps}
                            guidedStep={guidedStep}
                            onGuidedStepChange={(step) => {
                              stopListening(false);
                              setGuidedStep(step);
                            }}
                            guidedTranscripts={guidedTranscripts}
                            onGuidedTranscriptChange={(step, value) => {
                              setGuidedTranscripts((prev) => {
                                const updated = { ...prev, [step]: value };
                                guidedTranscriptsRef.current = updated;
                                return updated;
                              });
                            }}
                            speechSupported={speechSupported}
                            isListening={isListening}
                            speechError={speechError}
                            isSearchingMedicine={isSearchingMedicine}
                            selectedMedicineName={selectedMedicineName}
                            selectedMedicineSource={selectedMedicineSource}
                            showCustomConfirm={showCustomConfirm}
                            parseDraftPending={parseDraft.isPending}
                            onStartListening={startListening}
                            onStopListening={() => stopListening(false)}
                            onBack={() => {
                              stopListening(false);
                              setShowCustomConfirm(null);
                              setGuidedStep((prev) => Math.max(prev - 1, 1));
                            }}
                            onNext={() => {
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
                            onFinish={handleFinishGuidedPrefill}
                            onUseCustomMedicine={handleUseCustomMedicine}
                            onClearSelectedMedicine={clearSelectedMedicine}
                            onCustomConfirmAccept={() => {
                              handleUseCustomMedicine(showCustomConfirm?.name || "");
                              setShowCustomConfirm(null);
                              setGuidedStep(2);
                            }}
                            onCustomConfirmDismiss={() => {
                              setShowCustomConfirm(null);
                              setGuidedTranscripts((prev) => {
                                const updated = { ...prev, 1: "" };
                                guidedTranscriptsRef.current = updated;
                                return updated;
                              });
                            }}
                          />
                        )
                      ) : (
                        <PrescriptionMedicineForm
                          title={editingIndex !== null ? "Edit Medicine Details" : "Add Medicine Details"}
                          editingIndex={editingIndex}
                          selectedMedicineName={selectedMedicineName}
                          selectedMedicineSource={selectedMedicineSource}
                          searchQuery={searchQuery}
                          setSearchQuery={setSearchQuery}
                          medicineList={medicineList}
                          isSearchingMedicine={isSearchingMedicine}
                          medicineStatus={medicineStatus}
                          errors={errors as Record<string, { message?: string } | undefined>}
                          medicationType={medicationType}
                          dosage={dosage}
                          frequency={frequency}
                          meal={meal}
                          timingMorning={timingMorning}
                          timingAfternoon={timingAfternoon}
                          timingEvening={timingEvening}
                          timingNight={timingNight}
                          startDate={startDate}
                          endDate={endDate}
                          instructions={instructions}
                          medicationTypeOptions={medicationTypeOptions}
                          frequencyOptions={frequencyOptions}
                          mealOptions={mealOptions}
                          dosageOptions={dosageOptions}
                          onSelectMedicine={handleSelectMedicine}
                          onUseCustomMedicine={handleUseCustomMedicine}
                          onClearSelectedMedicine={clearSelectedMedicine}
                          onMedicationTypeChange={(value) => setValue("medication_type", value, { shouldValidate: true })}
                          onDosageChange={(value) => setValue("dosage", value, { shouldValidate: true })}
                          onFrequencyChange={(value) => setValue("frequency", value, { shouldValidate: true })}
                          onMealChange={(value) => setValue("meal", value as PrescriptionForm["meal"], { shouldValidate: true })}
                          onTimingChange={(name, value) => setValue(name, value)}
                          onStartDateChange={setStartDate}
                          onEndDateChange={setEndDate}
                          onInstructionsChange={(value) => setValue("instructions", value, { shouldValidate: true })}
                          onSubmit={handleAddOrUpdateMedicine}
                          onCancel={editingIndex !== null ? handleCancelEdit : undefined}
                          submitLabel={editingIndex !== null ? "Update Medicine" : "+ Add to Prescription List"}
                          fullWidthButton={editingIndex === null}
                        />
                      )}
                    </div>

                    <PrescriptionListPanel
                      addedMedicines={addedMedicines}
                      onEditMedicine={handleEditMedicine}
                      onDeleteMedicine={handleDeleteMedicine}
                      stampPreference={stampPreference}
                      stampOptions={stampOptions}
                      onStampChange={(value) => setValue("stamp_preference", value, { shouldValidate: true })}
                      onFinalSubmit={handleFinalSubmit}
                      addPrescriptionPending={addPrescription.isPending}
                      errors={errors as Record<string, { message?: string } | undefined>}
                      frequencyOptions={frequencyOptions}
                      mealOptions={mealOptions}
                      mobileTab={mobileTab}
                    />
                  </div>
                </div>
              )}
            </form>
          </div>
        </DialogContent>
      </Dialog>

      <PrescriptionSuccessDialog open={showSuccess} onClose={handleSuccessClose} />
    </>
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

function mergeTranscripts(base: string, final: string, interim: string): string {
  const b = base.trim();
  const f = final.trim();
  const i = interim.trim();

  if (!b) {
    return combineTranscript(f, i);
  }

  if (!f) {
    return combineTranscript(b, i);
  }

  const bLower = b.toLowerCase();
  const fLower = f.toLowerCase();

  if (fLower.startsWith(bLower)) {
    return combineTranscript(f, i);
  }

  const baseWords = b.split(/\s+/);
  const finalWords = f.split(/\s+/);

  let overlapLength = 0;
  const maxOverlap = Math.min(baseWords.length, finalWords.length);

  for (let len = maxOverlap; len > 0; len--) {
    const baseTail = baseWords.slice(-len).map((w) => w.toLowerCase()).join(" ");
    const finalHead = finalWords.slice(0, len).map((w) => w.toLowerCase()).join(" ");
    if (baseTail === finalHead) {
      overlapLength = len;
      break;
    }
  }

  if (overlapLength > 0) {
    const uniqueFinal = finalWords.slice(overlapLength).join(" ");
    return combineTranscript(b, uniqueFinal, i);
  }

  return combineTranscript(b, f, i);
}

function cleanDuplicateWords(text: string): string {
  if (!text) return "";
  const words = text.split(/\s+/);
  const result: string[] = [];
  for (let i = 0; i < words.length; i++) {
    const word = words[i];
    if (i > 0 && word.toLowerCase() === words[i - 1].toLowerCase()) {
      continue;
    }
    result.push(word);
  }
  return result.join(" ");
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
