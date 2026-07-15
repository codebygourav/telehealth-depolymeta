"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { Undo2, X, ClipboardList, Stethoscope, FileText, Mic } from "lucide-react";
import { useParams } from "next/navigation";
import { useEffect, useMemo, useRef, useState } from "react";
import { useForm, useWatch } from "react-hook-form";

import { getMedicines } from "@/api/medicines";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useAuth } from "@/context/userContext";
import { useAddPrescription } from "@/queries/useAddPrescription";
import { useSubmitConclusion } from "@/mutations/useSubmitConclusion";
import { useMedicines } from "@/queries/useMedicines";
import { useDoctorProfile } from "@/queries/useProfile";
import { useMedicineTemplates } from "@/queries/useMedicineTemplates";

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
  strength?: string | null;
  dosage?: string | null;
  frequency?: string | null;
  timing_morning?: boolean;
  timing_afternoon?: boolean;
  timing_evening?: boolean;
  timing_night?: boolean;
  meal?: PrescriptionForm["meal"] | null;
  application_area?: string | null;
  remarks?: string | null;
  follow_up_note?: string | null;
  instructions?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  stamp_preference?: string | null;
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
  initialTab?: "findings" | "medicines" | "reports";
  assistantConfig?: {
    enabled?: boolean;
    input_mode?: string;
    text_mode_max_chars?: number;
    speech_locale?: string;
    supported_locales?: string[];
    allow_custom_locale?: boolean;
    requires_doctor_review?: boolean;
    browser_speech_enabled?: boolean;
  } | null;
  initialMedicines?: any[];
  initialFindings?: string;
  initialNextVisitDate?: string;
  initialRecommendedTests?: string;
  initialGeneralNotes?: string;
}

type AssistantConfig = NonNullable<
  AddPrescriptionDialogProps["assistantConfig"]
>;

const defaultFormValues: PrescriptionForm = {
  medicine_id: "",
  medicine_name: "",
  medication_type: "tablet",
  strength: "",
  dosage: "",
  frequency: "",
  timing_morning: false,
  timing_afternoon: false,
  timing_evening: false,
  timing_night: false,
  meal: undefined as unknown as PrescriptionForm["meal"],
  application_area: "",
  remarks: "",
  follow_up_note: "",
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
  {
    id: 5,
    title: "Doctor Notes",
    hint: "Speak any general notes/patient instructions. Example: Drink warm water, avoid cold drinks.",
  },
];

export default function AddPrescriptionDialog({
  open,
  onOpenChange,
  initialTab,
  assistantConfig,
  initialMedicines = [],
  initialFindings = "",
  initialNextVisitDate = "",
  initialRecommendedTests = "",
  initialGeneralNotes = "",
}: AddPrescriptionDialogProps) {
  const { token } = useAuth();
  const params = useParams();
  const appointmentId = params?.id as string;

  const { data: profileResponse } = useDoctorProfile();
  const doctorVoiceLocale = profileResponse?.data?.voice_settings?.speech_locale;
  const doctorAiTraining = profileResponse?.data?.ai_training;
  const pronunciationDictionary = useMemo(
    () => doctorAiTraining?.pronunciation_dictionary ?? [],
    [doctorAiTraining?.pronunciation_dictionary],
  );
  const speechWordCorrections = useMemo(
    () => doctorAiTraining?.speech_word_corrections ?? [],
    [doctorAiTraining?.speech_word_corrections],
  );
  const aiInstructionSuggestions = useMemo(
    () =>
      (doctorAiTraining?.frequently_used_instructions ?? [])
        .filter((item) => item?.trim())
        .slice(0, 12),
    [doctorAiTraining?.frequently_used_instructions],
  );
  const aiCommonDiagnoses = useMemo(
    () =>
      (doctorAiTraining?.common_diagnoses ?? [])
        .filter((item) => item?.trim())
        .slice(0, 12),
    [doctorAiTraining?.common_diagnoses],
  );
  const aiProcedureSuggestions = useMemo(
    () =>
      (doctorAiTraining?.procedures_investigations ?? [])
        .filter((item) => item?.trim())
        .slice(0, 12),
    [doctorAiTraining?.procedures_investigations],
  );
  const medicineShortcuts = useMemo(
    () => doctorAiTraining?.medicine_shortcuts ?? [],
    [doctorAiTraining?.medicine_shortcuts],
  );

  const addPrescription = useAddPrescription(appointmentId || "", token!);
  const submitConclusionMutation = useSubmitConclusion();

  const assistantMode = assistantConfig?.input_mode || "off";
  const dictationEnabled =
    Boolean(assistantConfig?.enabled) &&
    (assistantMode === "text" || assistantMode === "speech");

  const browserVoiceEnabled = Boolean(assistantConfig?.browser_speech_enabled);

  const [entryMode, setEntryMode] = useState<EntryMode>(
    getDefaultEntryMode(dictationEnabled),
  );

  const templatesQuery = useMedicineTemplates();
  const templates = templatesQuery.data?.data ?? [];

  const handleApplyTemplate = (templateId: string) => {
    const selectedTemplate = templates.find((t) => t.id === templateId);
    if (!selectedTemplate) return;

    const newMeds: AddedMedicine[] = (selectedTemplate.items ?? []).map((item) => {
      const isCustom = !item.medicine_id;
      return {
        medicine_id: item.medicine_id || "",
        name: item.medicine_name,
        type: item.medicine_type || "Tablet",
        dosage: item.dosage || "",
        frequency: item.frequency || "OD",
        frequencylabel: item.frequency || "Once a day",
        meal: item.meal_timing || "after_meal",
        duration: item.duration_value ? `${item.duration_value} ${item.duration_type || "days"}` : "Ongoing",
        instructions: item.instructions || "",
        start_date: getTodayDate(),
        end_date: "",
        medicine_source: (isCustom ? "doctor_added" : "inventory") as MedicineSource,
        created_via: "template",
        timing_morning: item.frequency_times?.includes("08:00") || item.frequency_times?.some(t => t.toLowerCase().includes("morning")) || false,
        timing_afternoon: item.frequency_times?.includes("14:00") || item.frequency_times?.some(t => t.toLowerCase().includes("afternoon")) || false,
        timing_evening: item.frequency_times?.includes("18:00") || item.frequency_times?.some(t => t.toLowerCase().includes("evening")) || false,
        timing_night: item.frequency_times?.includes("21:00") || item.frequency_times?.some(t => t.toLowerCase().includes("night")) || false,
      };
    });

    setAddedMedicines(newMeds);
  };

  const [activeTab, setActiveTab] = useState<"prescribe" | "reports">(
    initialTab === "reports" ? "reports" : "prescribe"
  );

  // Findings States
  const [findingsText, setFindingsText] = useState("");
  const [nextVisitDate, setNextVisitDate] = useState("");
  const [isListeningFindings, setIsListeningFindings] = useState(false);

  // Diagnostics & Reports States
  const [includeReports, setIncludeReports] = useState(true);
  const [recommendedTests, setRecommendedTests] = useState("");
  const [reportType, setReportType] = useState("other");
  const [reportFiles, setReportFiles] = useState<File[]>([]);
  const [isListeningTests, setIsListeningTests] = useState(false);

  const toggleListeningFindings = () => {
    if (isListeningFindings) {
      (window as any)._findingsRec?.stop();
      setIsListeningFindings(false);
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

    rec.onresult = (event: any) => {
      for (let i = event.resultIndex; i < event.results.length; i++) {
        const transcript = event.results[i][0].transcript;
        if (event.results[i].isFinal) {
          const cleaned = applySpeechTrainingMappings(
            cleanDuplicateWords(transcript),
            pronunciationDictionary,
            speechWordCorrections,
          );
          if (cleaned) {
            setFindingsText((prev) => {
              const trimmedPrev = prev.trim();
              if (!trimmedPrev) return cleaned;
              if (trimmedPrev.endsWith(cleaned) || trimmedPrev.toLowerCase().includes(cleaned.toLowerCase())) {
                return trimmedPrev;
              }
              return `${trimmedPrev} ${cleaned}`;
            });
          }
        }
      }
    };

    rec.onerror = () => setIsListeningFindings(false);
    rec.onend = () => setIsListeningFindings(false);

    (window as any)._findingsRec = rec;
    setIsListeningFindings(true);
    rec.start();
  };

  const toggleListeningTests = () => {
    if (isListeningTests) {
      (window as any)._testsRec?.stop();
      setIsListeningTests(false);
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

    rec.onresult = (event: any) => {
      for (let i = event.resultIndex; i < event.results.length; i++) {
        const transcript = event.results[i][0].transcript;
        if (event.results[i].isFinal) {
          const cleaned = applySpeechTrainingMappings(
            cleanDuplicateWords(transcript),
            pronunciationDictionary,
            speechWordCorrections,
          );
          if (cleaned) {
            setRecommendedTests((prev) => {
              const trimmedPrev = prev.trim();
              if (!trimmedPrev) return cleaned;
              if (trimmedPrev.endsWith(cleaned) || trimmedPrev.toLowerCase().includes(cleaned.toLowerCase())) {
                return trimmedPrev;
              }
              return `${trimmedPrev} ${cleaned}`;
            });
          }
        }
      }
    };

    rec.onerror = () => setIsListeningTests(false);
    rec.onend = () => setIsListeningTests(false);

    (window as any)._testsRec = rec;
    setIsListeningTests(true);
    rec.start();
  };

  useEffect(() => {
    if (doctorVoiceLocale) {
      setSelectedSpeechLocale(doctorVoiceLocale as VoiceLocale);
    }
  }, [doctorVoiceLocale]);
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
  const [isListening, setIsListening] = useState(false);
  const [isApplyingVoiceStep, setIsApplyingVoiceStep] = useState(false);
  const [speechError, setSpeechError] = useState<string | null>(null);
  const [selectedSpeechLocale, setSelectedSpeechLocale] = useState<VoiceLocale>(
    () => getInitialVoiceLocale(assistantConfig?.speech_locale),
  );
  const voiceLanguageOptions = useMemo(
    () => buildVoiceLanguageOptions(assistantConfig),
    [assistantConfig],
  );

  const [addedMedicines, setAddedMedicines] = useState<AddedMedicine[]>([]);
  const [editingIndex, setEditingIndex] = useState<number | null>(null);
  const [selectedMedicineSource, setSelectedMedicineSource] =
    useState<MedicineSource>(null);
  const [selectedMedicineConfig, setSelectedMedicineConfig] =
    useState<MedicineItem | null>(null);

  const [voiceDraftMedicine, setVoiceDraftMedicine] =
    useState<DraftFormPayload | null>(null);
  const [missingFieldsList, setMissingFieldsList] = useState<string[]>([]);

  const [isSearchingMedicine, setIsSearchingMedicine] = useState(false);
  const [showCustomConfirm, setShowCustomConfirm] = useState<{
    name: string;
  } | null>(null);

  const [mobileTab, setMobileTab] = useState<"form" | "list">("form");
  const [generalNotes, setGeneralNotes] = useState("");
  const [toastMessage, setToastMessage] = useState<{
    text: string;
    type: "success" | "error";
  } | null>(null);

  const recognitionRef = useRef<BrowserSpeechRecognition | null>(null);
  const shouldParseAfterStopRef = useRef(false);
  const transcriptFinalRef = useRef("");
  const liveTranscriptRef = useRef("");
  const guidedTranscriptsRef = useRef<Record<number, string>>(
    createEmptyGuidedTranscripts(),
  );

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

  const selectedMedicineName =
    useWatch({ control, name: "medicine_name" }) || "";
  const medicationType = useWatch({ control, name: "medication_type" });
  const meal = useWatch({ control, name: "meal" });
  const dosage = useWatch({ control, name: "dosage" });
  const strength = useWatch({ control, name: "strength" });
  const frequency = useWatch({ control, name: "frequency" });
  const applicationArea = useWatch({ control, name: "application_area" });
  const remarks = useWatch({ control, name: "remarks" });
  const followUpNote = useWatch({ control, name: "follow_up_note" });
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
    [medicinesQuery.data?.data],
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
      setActiveTab(initialTab === "reports" ? "reports" : "prescribe");
      setFindingsText("");
      setNextVisitDate("");
      setRecommendedTests("");
      setGeneralNotes("");
      setIncludeReports(false);
      setAddedMedicines([]);
      return;
    }

    recognitionRef.current?.abort();
    recognitionRef.current = null;
    setIsListening(false);

    liveTranscriptRef.current = "";
    transcriptFinalRef.current = "";
    guidedTranscriptsRef.current = createEmptyGuidedTranscripts();

    reset(defaultFormValues);
    setDraftId(null);
    setSearchQuery("");
    setShowSuccess(false);
    setEntryMode(getDefaultEntryMode(dictationEnabled));
    setGuidedStep(1);
    setGuidedTranscripts(createEmptyGuidedTranscripts());
    setIsApplyingVoiceStep(false);
    setSpeechError(null);
    setSelectedSpeechLocale(
      getInitialVoiceLocale(assistantConfig?.speech_locale),
    );
    setStartDate(getTodayDate());
    setEndDate("");
    setAddedMedicines([]);
    setEditingIndex(null);
    setSelectedMedicineSource(null);
    setSelectedMedicineConfig(null);
    setVoiceDraftMedicine(null);
    setMissingFieldsList([]);
    setIsSearchingMedicine(false);
    setShowCustomConfirm(null);
    setMobileTab("form");
    setToastMessage(null);
    setGeneralNotes("");

    // Reset findings & diagnostics states
    setFindingsText("");
    setNextVisitDate("");
    setIncludeReports(false);
    setRecommendedTests("");
    setReportType("other");
    setReportFiles([]);
    setIsListeningFindings(false);
    setIsListeningTests(false);
    (window as any)._findingsRec?.abort();
    (window as any)._testsRec?.abort();
  }, [open, dictationEnabled, assistantConfig?.speech_locale, reset, initialTab]);

  useEffect(() => {
    if (!dictationEnabled && entryMode === "voice") {
      setEntryMode("manual");
    }
  }, [dictationEnabled, entryMode]);

  const resolvedMedicationTypeOptions = useMemo(() => {
    if (selectedMedicineConfig?.type) {
      const typeStr = String(selectedMedicineConfig.type).toLowerCase();
      const matched = medicationTypeOptions.find((o) => o.value === typeStr || o.label.toLowerCase() === typeStr);
      if (matched) {
        return [matched];
      }
      return [{ label: selectedMedicineConfig.type, value: typeStr }];
    }
    return medicationTypeOptions;
  }, [selectedMedicineConfig?.type]);

  const strengthOptions = useMemo(
    () => toOptionItems(selectedMedicineConfig?.strength_options),
    [selectedMedicineConfig?.strength_options],
  );

  const dosageOptions = useMemo(() => {
    const configured = toOptionItems(selectedMedicineConfig?.dosage_options);
    return configured.length > 0 ? configured : getDosageOptions(medicationType);
  }, [medicationType, selectedMedicineConfig?.dosage_options]);

  const resolvedFrequencyOptions = useMemo(() => {
    const configured = toFrequencyOptionItems(
      selectedMedicineConfig?.frequency_options,
    );
    return configured.length > 0 ? configured : frequencyOptions;
  }, [selectedMedicineConfig?.frequency_options]);

  const resolvedMealOptions = useMemo(() => {
    const configured = toMealOptionItems(selectedMedicineConfig?.meal_options);
    return configured.length > 0 ? configured : mealOptions;
  }, [selectedMedicineConfig?.meal_options]);

  const applicationAreaOptions = useMemo(
    () => toOptionItems(selectedMedicineConfig?.application_area_options),
    [selectedMedicineConfig?.application_area_options],
  );

  const durationOptions = useMemo(
    () => toOptionItems(selectedMedicineConfig?.duration_options),
    [selectedMedicineConfig?.duration_options],
  );

  const fieldRules = useMemo(
    () => normalizeFieldRules(selectedMedicineConfig?.field_rules),
    [selectedMedicineConfig?.field_rules],
  );

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
      description:
        "This name is not in the database. It will be saved as a custom medicine.",
    };
  }, [selectedMedicineSource, selectedMedicineName]);

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
    const configuredStrengths = medicine.strength_options || [];
    const configuredDosages = medicine.dosage_options || [];
    const configuredFrequencies = medicine.frequency_options || [];
    const configuredMeals = medicine.meal_options || [];
    const configuredAreas = medicine.application_area_options || [];

    if (configuredStrengths.length === 1) {
      setValue("strength", configuredStrengths[0], { shouldValidate: true });
    }
    if (configuredDosages.length === 1) {
      setValue("dosage", configuredDosages[0], { shouldValidate: true });
    }
    if (configuredFrequencies.length === 1) {
      setValue("frequency", configuredFrequencies[0], { shouldValidate: true });
    }
    if (configuredMeals.length === 1) {
      setValue("meal", configuredMeals[0] as PrescriptionForm["meal"], {
        shouldValidate: true,
      });
    }
    if (configuredAreas.length === 1) {
      setValue("application_area", configuredAreas[0], { shouldValidate: true });
    }

    setSelectedMedicineSource(medicine.source || "custom");
    setSelectedMedicineConfig(medicine);
    setSearchQuery("");
  };

  const handleUseCustomMedicine = (medicineName: string) => {
    const value = resolveMedicineShortcut(medicineName.trim(), medicineShortcuts);

    if (!value) {
      return;
    }

    setValue("medicine_id", "", { shouldValidate: true });
    setValue("medicine_name", value, { shouldValidate: true });
    setSelectedMedicineSource("custom");
    setSelectedMedicineConfig(null);
    setSearchQuery("");
  };

  const handleDurationPresetChange = (value: string) => {
    const parsedDuration = parseDurationFromSpeech(value, startDate);

    if (parsedDuration) {
      setStartDate(parsedDuration.startDate);
      setEndDate(parsedDuration.endDate);
    }
  };

  const clearSelectedMedicine = () => {
    setValue("medicine_id", "", { shouldValidate: true });
    setValue("medicine_name", "", { shouldValidate: true });
    setSelectedMedicineSource(null);
    setSelectedMedicineConfig(null);
    setSearchQuery("");
  };

  const setGuidedTranscriptValue = (step: number, value: string) => {
    const cleanedValue = value.trim();
    setGuidedTranscripts((prev) => {
      const updated = {
        ...prev,
        [step]: cleanedValue,
      };
      guidedTranscriptsRef.current = updated;
      return updated;
    });
  };

  const handleStep1Complete = async (text: string, shouldAdvance = true) => {
    const candidate = resolveMedicineShortcut(
      extractMedicineSearchCandidate(text),
      medicineShortcuts,
    );
    if (!candidate) {
      setSpeechError("Speak or type a medicine name.");
      return false;
    }

    setIsSearchingMedicine(true);
    setShowCustomConfirm(null);
    try {
      const response = await getMedicines({
        page: 1,
        per_page: 20,
        search: candidate,
        include_doctor_added: true,
      });

      const list = response?.data || [];
      const matched = findMedicineMatch(list, candidate);

      if (matched) {
        handleSelectMedicine(matched);
        setGuidedTranscriptValue(1, matched.name);
        setSpeechError(null);
        if (shouldAdvance) {
          setGuidedStep(2);
        }
        return true;
      } else {
        clearSelectedMedicine();
        setGuidedTranscriptValue(1, "");
        setShowCustomConfirm({ name: candidate });
      }
    } catch {
      clearSelectedMedicine();
      setGuidedTranscriptValue(1, "");
      setShowCustomConfirm({ name: candidate });
    } finally {
      setIsSearchingMedicine(false);
    }

    return false;
  };

  const handleAddOrUpdateMedicine = async () => {
    const fieldsToValidate =
      voiceDraftMedicine !== null
        ? (missingFieldsList as Array<keyof PrescriptionForm>)
        : ([
          "medicine_name",
          "medication_type",
          "dosage",
          "frequency",
          "meal",
        ] as Array<keyof PrescriptionForm>);

    const isValid = await trigger(fieldsToValidate);

    if (!isValid) {
      return;
    }

    const medicineName = getValues("medicine_name");
    const medicineId = getValues("medicine_id");
    const medicationTypeValue = getValues("medication_type");
    const strengthValue = getValues("strength");
    const dosageValue = getValues("dosage");
    const frequencyValue = getValues("frequency");
    const timingMorningValue = getValues("timing_morning");
    const timingAfternoonValue = getValues("timing_afternoon");
    const timingEveningValue = getValues("timing_evening");
    const timingNightValue = getValues("timing_night");
    const mealValue = getValues("meal");
    const applicationAreaValue = getValues("application_area");
    const remarksValue = getValues("remarks");
    const followUpNoteValue = getValues("follow_up_note");
    const instructionsValue = getValues("instructions");

    const newMedicine: AddedMedicine = {
      medicine_id: medicineId || null,
      medicine_name: medicineName.trim(),
      medication_type: medicationTypeValue,
      strength: strengthValue || "",
      dosage: dosageValue,
      frequency: frequencyValue,
      timing_morning: !!timingMorningValue,
      timing_afternoon: !!timingAfternoonValue,
      timing_evening: !!timingEveningValue,
      timing_night: !!timingNightValue,
      meal: mealValue,
      application_area: applicationAreaValue || "",
      remarks: remarksValue || "",
      instructions: instructionsValue || "",
      follow_up_note: followUpNoteValue || "",
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
      setToastMessage({
        text: `Updated ${newMedicine.medicine_name} successfully.`,
        type: "success",
      });
    } else {
      setAddedMedicines((prev) => [...prev, newMedicine]);
      setToastMessage({
        text: `Added ${newMedicine.medicine_name} to prescription list.`,
        type: "success",
      });
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
    setSelectedMedicineConfig(null);

    setGuidedTranscripts(createEmptyGuidedTranscripts());
    guidedTranscriptsRef.current = createEmptyGuidedTranscripts();
    setGuidedStep(1);
    liveTranscriptRef.current = "";
    transcriptFinalRef.current = "";
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
    setSelectedMedicineConfig(null);
  };

  const handleEditMedicine = (index: number) => {
    const med = addedMedicines[index];
    if (!med) return;

    setEditingIndex(index);

    setValue("medicine_id", med.medicine_id || "");
    setValue("medicine_name", med.medicine_name);
    setValue("medication_type", med.medication_type);
    setValue("strength", med.strength || "");
    setValue("dosage", med.dosage);
    setValue("frequency", med.frequency);
    setValue("timing_morning", med.timing_morning);
    setValue("timing_afternoon", med.timing_afternoon);
    setValue("timing_evening", med.timing_evening);
    setValue("timing_night", med.timing_night);
    setValue("meal", med.meal);
    setValue("application_area", med.application_area || "");
    setValue("remarks", med.remarks || "");
    setValue("instructions", med.instructions || "");
    setValue("follow_up_note", med.follow_up_note || "");

    setStartDate(med.start_date || getTodayDate());
    setEndDate(med.end_date || "");

    if (med.medicine_id) {
      setSelectedMedicineSource("inventory");
      setSelectedMedicineConfig(
        medicineList.find((medicine) => medicine.id === med.medicine_id) ||
        null,
      );
    } else {
      setSelectedMedicineSource("custom");
      setSelectedMedicineConfig(null);
    }

    setVoiceDraftMedicine(null);
    setMissingFieldsList([]);
    setMobileTab("form");
  };

  const handleDeleteMedicine = (index: number) => {
    const med = addedMedicines[index];
    setAddedMedicines((prev) => prev.filter((_, i) => i !== index));
    if (med) {
      setToastMessage({
        text: `Removed ${med.medicine_name} from list.`,
        type: "success",
      });
    }

    if (editingIndex === index) {
      handleCancelEdit();
    } else if (editingIndex !== null && editingIndex > index) {
      setEditingIndex(editingIndex - 1);
    }
  };

  const [submittingUnified, setSubmittingUnified] = useState(false);

  const handleFinalSubmit = async () => {
    const hasFindings = findingsText.trim() || nextVisitDate || (includeReports && (recommendedTests.trim() || reportFiles.length > 0));
    const cleanedFindings = sanitizeClinicalText(findingsText);
    const cleanedRecommendedTests = sanitizeClinicalText(recommendedTests);
    const cleanedGeneralNotes = sanitizeClinicalText(generalNotes);

    if (addedMedicines.length === 0 && !hasFindings) {
      alert("Please add clinical findings, diagnostics or at least one medicine to submit.");
      return;
    }

    const isStampValid = await trigger("stamp_preference");
    if (!isStampValid) {
      return;
    }

    setSubmittingUnified(true);

    try {
      if (hasFindings) {
        let combinedInstructions = cleanedFindings;
        if (includeReports && cleanedRecommendedTests) {
          combinedInstructions += `\n\nRecommended Tests:\n${cleanedRecommendedTests}`;
        }

        await submitConclusionMutation.mutateAsync({
          appointmentId,
          instructions_by_doctor: combinedInstructions || "Consultation conclusion submitted.",
          next_visit_date: nextVisitDate || getTodayDate(),
          type: includeReports ? reportType : undefined,
          files: includeReports ? reportFiles : [],
        });
      }

      if (addedMedicines.length > 0) {
        const stampPref = getValues("stamp_preference");
        const medicinesPayload = addedMedicines.map((med) => {
          const timings: string[] = [];
          if (med.timing_morning) timings.push("morning");
          if (med.timing_afternoon) timings.push("afternoon");
          if (med.timing_evening) timings.push("evening");
          if (med.timing_night) timings.push("night");

          return {
            medicine_id: med.medicine_id || null,
            medicine_name: (med.medicine_name || "").trim(),
            medication_type: med.medication_type || "tablet",
            strength: med.strength || "",
            dosage: med.dosage,
            frequency: med.frequency || "OD",
            timings,
            meal: med.meal,
            application_area: med.application_area || "",
            remarks: med.remarks || "",
            start_date: med.start_date || getTodayDate(),
            end_date: med.end_date || null,
            instructions: med.instructions || "",
            follow_up_note: med.follow_up_note || "",
          };
        });

        const payload = {
          draft_id: draftId,
          stamp_preference: stampPref,
          follow_up_note: [
            cleanedFindings ? `Clinical Findings:\n${cleanedFindings}` : "",
            cleanedRecommendedTests ? `Recommended Tests / Diagnostics:\n${cleanedRecommendedTests}` : "",
            cleanedGeneralNotes,
            ...addedMedicines.map((med) => sanitizeClinicalText(med.follow_up_note || "")),
          ].filter(Boolean).join("\n\n"),
          medicines: medicinesPayload,
        };

        await addPrescription.mutateAsync(payload);
      }

      setShowSuccess(true);
    } catch (error: any) {
      console.error("Unified submit error:", error);
      alert(
        error?.response?.data?.errors?.message ||
        error?.message ||
        "Failed to save. Please try again."
      );
    } finally {
      setSubmittingUnified(false);
    }
  };

  const handleSuccessClose = () => {
    setShowSuccess(false);
    onOpenChange(false);
  };

  const getCurrentVoiceDraftPayload = (): DraftFormPayload => ({
    medicine_id: getValues("medicine_id") || null,
    medicine_name: getValues("medicine_name") || null,
    medicine_source:
      selectedMedicineSource === "inventory" ||
        selectedMedicineSource === "doctor_added"
        ? selectedMedicineSource
        : selectedMedicineSource === "custom"
          ? null
          : null,
    medication_type: getValues("medication_type") || null,
    strength: getValues("strength") || null,
    dosage: getValues("dosage") || null,
    frequency: getValues("frequency") || null,
    timing_morning: !!getValues("timing_morning"),
    timing_afternoon: !!getValues("timing_afternoon"),
    timing_evening: !!getValues("timing_evening"),
    timing_night: !!getValues("timing_night"),
    meal: getValues("meal") || null,
    application_area: getValues("application_area") || null,
    remarks: getValues("remarks") || null,
    follow_up_note: getValues("follow_up_note") || null,
    instructions: getValues("instructions") || null,
    start_date: startDate || null,
    end_date: endDate || null,
    stamp_preference: getValues("stamp_preference") || null,
  });

  const getMissingVoiceFields = (): string[] => {
    const missing: string[] = [];

    if (!(getValues("medicine_name") || "").trim()) {
      missing.push("medicine_name");
    }
    if (!(getValues("medication_type") || "").trim()) {
      missing.push("medication_type");
    }
    if (!(getValues("dosage") || "").trim()) {
      missing.push("dosage");
    }
    if (!(getValues("frequency") || "").trim()) {
      missing.push("frequency");
    }
    if (!getValues("meal")) {
      missing.push("meal");
    }

    return missing;
  };

  const commitTypeAndDosageStep = (
    transcript: string,
    shouldAdvance = true,
  ) => {
    const parsedType = parseMedicationTypeFromSpeech(transcript);
    const dosageSourceType = parsedType || getValues("medication_type") || "";
    const parsedStrength = parseStrengthFromSpeech(transcript);
    const parsedDosage = parseDosageFromSpeech(transcript, dosageSourceType);

    if (!parsedType && !parsedStrength && !parsedDosage) {
      setSpeechError("Could not confirm the medicine type or dosage.");
      return false;
    }

    if (parsedType) {
      setValue("medication_type", parsedType, { shouldValidate: true });
    }
    if (parsedStrength) {
      setValue("strength", parsedStrength, { shouldValidate: true });
    }
    if (parsedDosage) {
      setValue("dosage", parsedDosage, { shouldValidate: true });
    }

    setGuidedTranscriptValue(
      2,
      combineSummaryParts([
        parsedType ? getMedicationTypeLabel(parsedType) : "",
        parsedStrength,
        parsedDosage,
      ]),
    );
    setSpeechError(null);

    if (shouldAdvance) {
      setGuidedStep(3);
    }

    return true;
  };

  const commitFrequencyAndTimingsStep = (
    transcript: string,
    shouldAdvance = true,
  ) => {
    let parsedFrequency = parseFrequencyFromSpeech(transcript);
    const parsedTimings = parseTimingFlagsFromSpeech(transcript);

    if (!parsedFrequency) {
      parsedFrequency = inferFrequencyFromTimings(parsedTimings);
    }

    if (!parsedFrequency && countActiveTimings(parsedTimings) === 0) {
      setSpeechError("Could not confirm the frequency or timing.");
      return false;
    }

    const resolvedTimings = applyFrequencyDefaults(
      parsedFrequency,
      parsedTimings,
    );

    if (parsedFrequency) {
      setValue("frequency", parsedFrequency, { shouldValidate: true });
    }
    setValue("timing_morning", resolvedTimings.timing_morning);
    setValue("timing_afternoon", resolvedTimings.timing_afternoon);
    setValue("timing_evening", resolvedTimings.timing_evening);
    setValue("timing_night", resolvedTimings.timing_night);

    setGuidedTranscriptValue(
      3,
      combineSummaryParts([
        parsedFrequency ? getFrequencyLabel(parsedFrequency) : "",
        getTimingLabels(resolvedTimings).join(", "),
      ]),
    );
    setSpeechError(null);

    if (shouldAdvance) {
      setGuidedStep(4);
    }

    return true;
  };

  const commitMealDurationNotesStep = (
    transcript: string,
    shouldAdvance = false,
  ) => {
    const parsedMeal = parseMealRelationFromSpeech(transcript);
    const parsedDuration = parseDurationFromSpeech(transcript, startDate);
    const parsedApplicationArea = parseApplicationAreaFromSpeech(
      transcript,
      applicationAreaOptions.map((option) => option.value),
    );
    const parsedFollowUp = extractFollowUpNote(transcript);
    const parsedInstructions = extractVoiceInstructions(transcript);

    if (
      !parsedMeal &&
      !parsedDuration &&
      !parsedApplicationArea &&
      !parsedFollowUp &&
      !parsedInstructions
    ) {
      setSpeechError("Could not confirm the meal instructions or notes.");
      return false;
    }

    if (parsedMeal) {
      setValue("meal", parsedMeal, { shouldValidate: true });
    }
    if (parsedInstructions) {
      setValue("instructions", parsedInstructions, { shouldValidate: true });
    }
    if (parsedApplicationArea) {
      setValue("application_area", parsedApplicationArea, {
        shouldValidate: true,
      });
    }
    if (parsedFollowUp) {
      setValue("follow_up_note", parsedFollowUp, { shouldValidate: true });
    }
    if (parsedDuration) {
      setStartDate(parsedDuration.startDate);
      setEndDate(parsedDuration.endDate);
    }

    setGuidedTranscriptValue(
      4,
      combineSummaryParts([
        parsedMeal ? getMealLabel(parsedMeal) : "",
        parsedDuration?.label || "",
        parsedApplicationArea,
        parsedFollowUp ? `Follow-up: ${parsedFollowUp}` : "",
        parsedInstructions,
      ]),
    );
    setSpeechError(null);

    if (shouldAdvance) {
      setGuidedStep(5);
    }

    return true;
  };

  const commitDoctorNotesStep = (
    transcript: string,
    shouldAdvance = false,
  ) => {
    const trimmed = transcript.trim();
    setGeneralNotes(trimmed);
    setGuidedTranscriptValue(5, trimmed);
    setSpeechError(null);

    return true;
  };

  const commitGuidedStep = async (
    step: number,
    transcript: string,
    shouldAdvance = true,
  ) => {
    const trimmed = applySpeechTrainingMappings(
      cleanDuplicateWords(transcript.trim()),
      pronunciationDictionary,
      speechWordCorrections,
    );

    if (!trimmed) {
      return true;
    }

    if (step === 1) {
      return handleStep1Complete(trimmed, shouldAdvance);
    }

    if (step === 2) {
      return commitTypeAndDosageStep(trimmed, shouldAdvance);
    }

    if (step === 3) {
      return commitFrequencyAndTimingsStep(trimmed, shouldAdvance);
    }

    if (step === 4) {
      return commitMealDurationNotesStep(trimmed, shouldAdvance);
    }

    if (step === 5) {
      return commitDoctorNotesStep(trimmed, shouldAdvance);
    }

    return true;
  };

  const openVoiceReview = async () => {
    const missing = getMissingVoiceFields();

    if (missing.length === 0) {
      await handleAddOrUpdateMedicine();
      return;
    }

    setVoiceDraftMedicine(getCurrentVoiceDraftPayload());
    setMissingFieldsList(missing);
  };

  const handleNextGuidedStep = async () => {
    const currentTranscript = (
      guidedTranscriptsRef.current[guidedStep] || ""
    ).trim();

    if (!currentTranscript) {
      if (guidedStep === 1) {
        setSpeechError("Speak or type a medicine name.");
        return;
      }

      setSpeechError(null);
      setGuidedStep((prev) => Math.min(prev + 1, guidedVoiceSteps.length));
      return;
    }

    await commitGuidedStep(guidedStep, currentTranscript, true);
  };

  const handleFinishGuidedPrefill = async () => {
    if (isListening) {
      shouldParseAfterStopRef.current = true;
      recognitionRef.current?.stop();
      return;
    }

    setIsApplyingVoiceStep(true);

    try {
      const currentTranscript = (
        guidedTranscriptsRef.current[guidedStep] || ""
      ).trim();

      if (currentTranscript) {
        const didCommit = await commitGuidedStep(
          guidedStep,
          currentTranscript,
          false,
        );

        if (!didCommit) {
          return;
        }
      }

      if (!(getValues("medicine_name") || "").trim()) {
        setSpeechError("Add a medicine name before applying the voice draft.");
        return;
      }

      setSpeechError(null);
      await openVoiceReview();
    } finally {
      setIsApplyingVoiceStep(false);
    }
  };

  function stopListening(shouldPrefill: boolean) {
    shouldParseAfterStopRef.current = shouldPrefill;

    if (!recognitionRef.current) {
      if (shouldPrefill) {
        void handleFinishGuidedPrefill();
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
    transcriptFinalRef.current = "";
    liveTranscriptRef.current = "";
    shouldParseAfterStopRef.current = false;

    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = getBrowserSpeechLocale(
      selectedSpeechLocale,
      assistantConfig?.speech_locale,
    );
    recognition.maxAlternatives = 1;

    recognition.onresult = (event) => {
      let finalTranscript = "";
      let interimTranscript = "";

      for (
        let index = 0;
        index < event.results.length;
        index += 1
      ) {
        const result = event.results[index];
        const transcript = result?.[0]?.transcript;

        if (!transcript) {
          continue;
        }

        if (result.isFinal) {
          finalTranscript = combineTranscript(finalTranscript, transcript);
        } else {
          interimTranscript = combineTranscript(interimTranscript, transcript);
        }
      }

      transcriptFinalRef.current = cleanDuplicateWords(finalTranscript);
      liveTranscriptRef.current = cleanDuplicateWords(
        combineTranscript(transcriptFinalRef.current, interimTranscript),
      );

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
      const spokenTranscript = applySpeechTrainingMappings(
        cleanDuplicateWords(liveTranscriptRef.current),
        pronunciationDictionary,
        speechWordCorrections,
      );

      shouldParseAfterStopRef.current = false;
      setIsListening(false);
      recognitionRef.current = null;

      if (shouldPrefill) {
        if (spokenTranscript) {
          setGuidedTranscriptValue(guidedStep, spokenTranscript);
        }
        void handleFinishGuidedPrefill();
      } else {
        if (spokenTranscript) {
          void commitGuidedStep(guidedStep, spokenTranscript, guidedStep < 4);
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
        <DialogContent className="w-[95vw] max-h-[92vh] sm:max-w-6xl! rounded-[28px] p-0 overflow-hidden flex flex-col gap-0! fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 border border-slate-200 bg-linear-to-br from-white via-slate-50 to-sky-50 shadow-[0_30px_90px_rgba(15,23,42,0.18)]">
          {toastMessage && (
            <div className="absolute top-4 left-1/2 -translate-x-1/2 z-100 animate-in fade-in slide-in-from-top-4 duration-300">
              <div
                className={`flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-semibold shadow-lg border ${toastMessage.type === "success" ? "bg-green-600 text-white border-green-700" : "bg-red-600 text-white border-red-700"}`}
              >
                <span>{toastMessage.text}</span>
                <button
                  type="button"
                  onClick={() => setToastMessage(null)}
                  className="rounded-full hover:bg-white/10 p-0.5"
                >
                  <X className="h-3 w-3" />
                </button>
              </div>
            </div>
          )}

          <DialogHeader className="border-b border-slate-200 px-5 py-4 sm:px-6 sm:py-5 pr-14 sm:pr-20 shrink-0 bg-white/70 backdrop-blur">
            <div className="flex items-start justify-between gap-4 flex-wrap">
              <div className="space-y-1">
                <div className="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-sky-700">
                  Add Prescription
                </div>
                <DialogTitle className="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                  Build a new prescription
                </DialogTitle>
                
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

          {entryMode !== null && (
            <div className="flex md:hidden border-b bg-background px-4 py-3 shrink-0">
              <div className="flex w-full bg-muted/20 p-1 rounded-lg border">
                <button
                  type="button"
                  onClick={() => setMobileTab("form")}
                  className={`flex-1 py-2 text-xs font-bold rounded-lg text-center transition-all ${mobileTab === "form" ? "bg-primary text-white shadow-sm" : "text-muted-foreground"}`}
                >
                  {entryMode === "voice" ? "Voice Assistant" : "Manual Form"}
                </button>
                <button
                  type="button"
                  onClick={() => setMobileTab("list")}
                  className={`flex-1 py-2 text-xs font-bold rounded-lg text-center transition-all relative ${mobileTab === "list" ? "bg-primary text-white shadow-sm" : "text-muted-foreground"}`}
                >
                  Prescription List
                  {addedMedicines.length > 0 && (
                    <span className="absolute top-1/2 -translate-y-1/2 right-2.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-primary text-[9px] font-bold text-primary-foreground shadow-sm">
                      {addedMedicines.length}
                    </span>
                  )}
                </button>
              </div>
            </div>
          )}

          <div className="flex-1 overflow-y-auto p-3 sm:p-6 min-h-0 bg-[linear-gradient(180deg,rgba(248,250,252,0.92),rgba(255,255,255,1))]">
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
                <div className="space-y-5">
                  <div className="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6 items-start">
                    <div
                      className={`md:col-span-7  p-0 sm:p-0  ${mobileTab === "form" ? "block" : "hidden md:block"}`}
                    >
                      {/* Sub-Tabs */}
                      <div className="flex mb-5 gap-2 fEnable Desktop Pushlex-wrap rounded-lg border border-slate-100 bg-slate-50 p-1.5 shadow-sm justify-center">
                        <button
                          type="button"
                          onClick={() => setActiveTab("prescribe")}
                          className={`flex items-center gap-2 px-2 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${activeTab === "prescribe"
                            ? "bg-muted text-black shadow-sm ring-1 ring-slate-200"
                            : "text-slate-500 hover:bg-white/70"
                            }`}
                        >
                          <Stethoscope className="h-4 w-4" />
                          Prescribe Medicine
                        </button>
                        <button
                          type="button"
                          onClick={() => setActiveTab("reports")}
                          className={`flex items-center gap-2 px-2 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all ${activeTab === "reports"
                            ? "bg-muted text-black shadow-sm ring-1 ring-slate-200"
                            : "text-slate-500 hover:bg-white/70"
                            }`}
                        >
                          <FileText className="h-4 w-4" />
                          Medical Report
                        </button>
                      </div>



                      {activeTab === "prescribe" && (
                        <div className="space-y-5 animate-in fade-in duration-200">
                          {/* Step 1: Clinical Findings & Notes */}
                          <div className="space-y-3 pb-5 border-b border-slate-200">
                            <div>
                              <h3 className="text-xs font-bold uppercase tracking-[0.2em] text-sky-700">Clinical Findings & Notes</h3>
                              <p className="text-[11px] text-slate-500">Document symptoms, diagnosis, and observations. This section appears on the prescription PDF.</p>
                            </div>

                            <div className="space-y-1.5">
                              <div className="flex items-center justify-between">
                                <label className="text-xs font-semibold text-muted-foreground">Clinical Findings / Diagnosis</label>
                                <button
                                  type="button"
                                  onClick={toggleListeningFindings}
                                  className={`p-1.5 rounded-full border transition-all ${isListeningFindings
                                    ? "bg-red-500 text-white border-red-500 animate-pulse shadow-sm"
                                    : "bg-blue-50 hover:bg-blue-100/80 text-blue-600 border-blue-200 shadow-sm"
                                    }`}
                                  title="Dictate findings"
                                >
                                  <Mic className="h-4 w-4" />
                                </button>
                              </div>
                              <Textarea
                                value={findingsText}
                                onChange={(e) => setFindingsText(e.target.value)}
                                placeholder="Enter or dictate patient findings, symptoms, diagnosis, and notes..."
                                rows={3}
                                className="resize-none text-sm rounded-2xl border border-slate-200 bg-white shadow-inner"
                              />
                              {aiCommonDiagnoses.length > 0 && (
                                <div className="flex flex-wrap gap-1.5 pt-1">
                                  {aiCommonDiagnoses.map((item) => (
                                    <button
                                      key={item}
                                      type="button"
                                      onClick={() => {
                                        const existing = findingsText.trim();
                                        const next = existing
                                          ? `${existing}${existing.endsWith("\n") ? "" : "\n"}${item}`
                                          : item;
                                        setFindingsText(next);
                                      }}
                                      className="text-[10px] px-2 py-0.5 rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition"
                                    >
                                      {item}
                                    </button>
                                  ))}
                                </div>
                              )}
                            </div>

                            <div className="space-y-1.5">
                              <label className="text-xs font-semibold text-slate-600">Next Follow-up Date</label>
                              <Input
                                type="date"
                                value={nextVisitDate}
                                onChange={(e) => setNextVisitDate(e.target.value)}
                                className="text-sm rounded-2xl border border-slate-200 bg-white"
                              />
                            </div>
                          </div>

                          <div className="pt-2">
                            <h3 className="text-xs font-bold text-slate-500 uppercase tracking-[0.2em]">Prescribe Medicines</h3>
                          </div>
                        </div>
                      )}

                      {activeTab === "prescribe" && (
                        <div className="animate-in fade-in duration-200">
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
                                errors={
                                  errors as Record<
                                    string,
                                    { message?: string } | undefined
                                  >
                                }
                                medicationType={medicationType}
                                strength={strength}
                                dosage={dosage}
                                frequency={frequency}
                                meal={meal}
                                applicationArea={applicationArea}
                                remarks={remarks}
                                followUpNote={followUpNote}
                                timingMorning={timingMorning}
                                timingAfternoon={timingAfternoon}
                                timingEvening={timingEvening}
                                timingNight={timingNight}
                                startDate={startDate}
                                endDate={endDate}
                                instructions={instructions}
                                medicationTypeOptions={resolvedMedicationTypeOptions}
                                strengthOptions={strengthOptions}
                                frequencyOptions={resolvedFrequencyOptions}
                                mealOptions={resolvedMealOptions}
                                dosageOptions={dosageOptions}
                                applicationAreaOptions={applicationAreaOptions}
                                durationOptions={durationOptions}
                                fieldRules={fieldRules}
                                onSelectMedicine={handleSelectMedicine}
                                onUseCustomMedicine={handleUseCustomMedicine}
                                onClearSelectedMedicine={clearSelectedMedicine}
                                onMedicationTypeChange={(value) =>
                                  setValue("medication_type", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onStrengthChange={(value) =>
                                  setValue("strength", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onDosageChange={(value) =>
                                  setValue("dosage", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onFrequencyChange={(value) =>
                                  setValue("frequency", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onMealChange={(value) =>
                                  setValue(
                                    "meal",
                                    value as PrescriptionForm["meal"],
                                    { shouldValidate: true },
                                  )
                                }
                                onApplicationAreaChange={(value) =>
                                  setValue("application_area", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onDurationPresetChange={handleDurationPresetChange}
                                onRemarksChange={(value) =>
                                  setValue("remarks", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onFollowUpNoteChange={(value) =>
                                  setValue("follow_up_note", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onTimingChange={(name, value) =>
                                  setValue(name, value)
                                }
                                onStartDateChange={setStartDate}
                                onEndDateChange={setEndDate}
                                onInstructionsChange={(value) =>
                                  setValue("instructions", value, {
                                    shouldValidate: true,
                                  })
                                }
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
                                errors={
                                  errors as Record<
                                    string,
                                    { message?: string } | undefined
                                  >
                                }
                                medicationType={medicationType}
                                strength={strength}
                                dosage={dosage}
                                frequency={frequency}
                                meal={meal}
                                applicationArea={applicationArea}
                                remarks={remarks}
                                followUpNote={followUpNote}
                                timingMorning={timingMorning}
                                timingAfternoon={timingAfternoon}
                                timingEvening={timingEvening}
                                timingNight={timingNight}
                                startDate={startDate}
                                endDate={endDate}
                                instructions={instructions}
                                medicationTypeOptions={medicationTypeOptions}
                                strengthOptions={strengthOptions}
                                frequencyOptions={resolvedFrequencyOptions}
                                mealOptions={resolvedMealOptions}
                                dosageOptions={dosageOptions}
                                applicationAreaOptions={applicationAreaOptions}
                                durationOptions={durationOptions}
                                fieldRules={fieldRules}
                                visibleFields={
                                  missingFieldsList as Array<
                                    | "medicine_name"
                                    | "medication_type"
                                    | "strength"
                                    | "dosage"
                                    | "frequency"
                                    | "meal"
                                    | "application_area"
                                    | "remarks"
                                    | "follow_up_note"
                                  >
                                }
                                mode="compact"
                                onSelectMedicine={handleSelectMedicine}
                                onUseCustomMedicine={handleUseCustomMedicine}
                                onClearSelectedMedicine={clearSelectedMedicine}
                                onMedicationTypeChange={(value) =>
                                  setValue("medication_type", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onStrengthChange={(value) =>
                                  setValue("strength", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onDosageChange={(value) =>
                                  setValue("dosage", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onFrequencyChange={(value) =>
                                  setValue("frequency", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onMealChange={(value) =>
                                  setValue(
                                    "meal",
                                    value as PrescriptionForm["meal"],
                                    { shouldValidate: true },
                                  )
                                }
                                onApplicationAreaChange={(value) =>
                                  setValue("application_area", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onDurationPresetChange={handleDurationPresetChange}
                                onRemarksChange={(value) =>
                                  setValue("remarks", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onFollowUpNoteChange={(value) =>
                                  setValue("follow_up_note", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onTimingChange={(name, value) =>
                                  setValue(name, value)
                                }
                                onStartDateChange={setStartDate}
                                onEndDateChange={setEndDate}
                                onInstructionsChange={(value) =>
                                  setValue("instructions", value, {
                                    shouldValidate: true,
                                  })
                                }
                                onSubmit={handleAddOrUpdateMedicine}
                                submitLabel="Confirm & Add"
                                cancelLabel="Discard"
                                onCancel={() => {
                                  setVoiceDraftMedicine(null);
                                  setMissingFieldsList([]);
                                  const currentStamp =
                                    getValues("stamp_preference");
                                  reset({
                                    ...defaultFormValues,
                                    stamp_preference: currentStamp,
                                  });
                                  setStartDate(getTodayDate());
                                  setEndDate("");
                                  setSelectedMedicineSource(null);
                                  setSelectedMedicineConfig(null);
                                }}
                              />
                            ) : (
                              <PrescriptionVoiceAssistantPanel
                                browserVoiceEnabled={browserVoiceEnabled}
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
                                  setGuidedTranscriptValue(step, value);
                                }}
                                speechSupported={speechSupported}
                                isListening={isListening}
                                speechError={speechError}
                                isSearchingMedicine={isSearchingMedicine}
                                selectedMedicineName={selectedMedicineName}
                                selectedMedicineSource={selectedMedicineSource}
                                showCustomConfirm={showCustomConfirm}
                                isFinishing={isApplyingVoiceStep}
                                onStartListening={startListening}
                                onStopListening={() => stopListening(false)}
                                onBack={() => {
                                  stopListening(false);
                                  setShowCustomConfirm(null);
                                  setGuidedStep((prev) => Math.max(prev - 1, 1));
                                }}
                                onNext={() => {
                                  stopListening(false);
                                  void handleNextGuidedStep();
                                }}
                                onFinish={() => {
                                  void handleFinishGuidedPrefill();
                                }}
                                onClearSelectedMedicine={clearSelectedMedicine}
                                onCustomConfirmAccept={() => {
                                  handleUseCustomMedicine(
                                    showCustomConfirm?.name || "",
                                  );
                                  setGuidedTranscriptValue(
                                    1,
                                    showCustomConfirm?.name || "",
                                  );
                                  setSpeechError(null);
                                  setShowCustomConfirm(null);
                                  setGuidedStep(2);
                                }}
                                onCustomConfirmDismiss={() => {
                                  setShowCustomConfirm(null);
                                  setGuidedTranscriptValue(1, "");
                                }}
                                medicationTypeOptions={resolvedMedicationTypeOptions}
                                dosageOptions={dosageOptions}
                                strengthOptions={strengthOptions}
                                frequencyOptions={resolvedFrequencyOptions}
                                mealOptions={resolvedMealOptions}
                                durationOptions={durationOptions}
                                applicationAreaOptions={applicationAreaOptions}
                                commonInstructionSuggestions={aiInstructionSuggestions}
                              />
                            )
                          ) : (
                            <PrescriptionMedicineForm
                              title={
                                editingIndex !== null
                                  ? "Edit Medicine Details"
                                  : "Add Medicine Details"
                              }
                              editingIndex={editingIndex}
                              selectedMedicineName={selectedMedicineName}
                              selectedMedicineSource={selectedMedicineSource}
                              searchQuery={searchQuery}
                              setSearchQuery={setSearchQuery}
                              medicineList={medicineList}
                              isSearchingMedicine={isSearchingMedicine}
                              medicineStatus={medicineStatus}
                              errors={
                                errors as Record<
                                  string,
                                  { message?: string } | undefined
                                >
                              }
                              medicationType={medicationType}
                              strength={strength}
                              dosage={dosage}
                              frequency={frequency}
                              meal={meal}
                              applicationArea={applicationArea}
                              remarks={remarks}
                              followUpNote={followUpNote}
                              timingMorning={timingMorning}
                              timingAfternoon={timingAfternoon}
                              timingEvening={timingEvening}
                              timingNight={timingNight}
                              startDate={startDate}
                              endDate={endDate}
                              instructions={instructions}
                              medicationTypeOptions={resolvedMedicationTypeOptions}
                              strengthOptions={strengthOptions}
                              frequencyOptions={resolvedFrequencyOptions}
                              mealOptions={resolvedMealOptions}
                              dosageOptions={dosageOptions}
                              applicationAreaOptions={applicationAreaOptions}
                              durationOptions={durationOptions}
                              fieldRules={fieldRules}
                              onSelectMedicine={handleSelectMedicine}
                              onUseCustomMedicine={handleUseCustomMedicine}
                              onClearSelectedMedicine={clearSelectedMedicine}
                              onMedicationTypeChange={(value) =>
                                setValue("medication_type", value, {
                                  shouldValidate: true,
                                })
                              }
                              onStrengthChange={(value) =>
                                setValue("strength", value, {
                                  shouldValidate: true,
                                })
                              }
                              onDosageChange={(value) =>
                                setValue("dosage", value, { shouldValidate: true })
                              }
                              onFrequencyChange={(value) =>
                                setValue("frequency", value, {
                                  shouldValidate: true,
                                })
                              }
                              onMealChange={(value) =>
                                setValue(
                                  "meal",
                                  value as PrescriptionForm["meal"],
                                  { shouldValidate: true },
                                )
                              }
                              onApplicationAreaChange={(value) =>
                                setValue("application_area", value, {
                                  shouldValidate: true,
                                })
                              }
                              onDurationPresetChange={handleDurationPresetChange}
                              onRemarksChange={(value) =>
                                setValue("remarks", value, {
                                  shouldValidate: true,
                                })
                              }
                              onFollowUpNoteChange={(value) =>
                                setValue("follow_up_note", value, {
                                  shouldValidate: true,
                                })
                              }
                              onTimingChange={(name, value) =>
                                setValue(name, value)
                              }
                              onStartDateChange={setStartDate}
                              onEndDateChange={setEndDate}
                              onInstructionsChange={(value) =>
                                setValue("instructions", value, {
                                  shouldValidate: true,
                                })
                              }
                              onSubmit={handleAddOrUpdateMedicine}
                              onCancel={
                                editingIndex !== null ? handleCancelEdit : undefined
                              }
                              submitLabel={
                                editingIndex !== null
                                  ? "Update Medicine"
                                  : "+ Add to Prescription List"
                              }
                              fullWidthButton={editingIndex === null}
                            />
                          )}
                        </div>
                      )}

                      {activeTab === "reports" && (
                        <div className="space-y-5 animate-in fade-in duration-200">
                          <div className="flex items-center justify-between p-3.5 bg-white border border-slate-200 rounded-2xl shadow-sm">
                            <div>
                              <h4 className="text-sm font-bold text-slate-900">Recommend Tests & Upload Reports</h4>
                              <p className="text-xs text-slate-500">Add investigations or upload supporting records in the same flow.</p>
                            </div>
                            <label className="relative inline-flex items-center cursor-pointer">
                              <input
                                type="checkbox"
                                checked={includeReports}
                                onChange={(e) => setIncludeReports(e.target.checked)}
                                className="sr-only peer"
                              />
                              <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                          </div>

                          {includeReports && (
                            <div className="space-y-5 animate-in fade-in slide-in-from-top-2 duration-200">
                              <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                  <label className="text-xs font-semibold text-slate-600">Recommend Tests / Reports (to Patients)</label>
                                  <button
                                    type="button"
                                    onClick={toggleListeningTests}
                                    className={`p-1.5 rounded-full border transition-all ${isListeningTests
                                      ? "bg-red-500 text-white border-red-500 animate-pulse shadow-sm"
                                      : "bg-blue-50 hover:bg-blue-100/80 text-blue-600 border-blue-200 shadow-sm"
                                      }`}
                                    title="Dictate tests"
                                  >
                                    <Mic className="h-4 w-4" />
                                  </button>
                                </div>
                                <Textarea
                                  value={recommendedTests}
                                  onChange={(e) => setRecommendedTests(e.target.value)}
                                  placeholder="E.g. Complete Blood Count (CBC), Chest X-Ray, Blood sugar fasting..."
                                  rows={4}
                                  className="resize-none text-sm rounded-2xl border border-slate-200 bg-white shadow-inner"
                                />
                                {aiProcedureSuggestions.length > 0 && (
                                  <div className="flex flex-wrap gap-1.5 pt-1">
                                    {aiProcedureSuggestions.map((item) => (
                                      <button
                                        key={item}
                                        type="button"
                                        onClick={() => {
                                          const existing = recommendedTests.trim();
                                          const next = existing
                                            ? `${existing}${existing.endsWith("\n") ? "" : "\n"}${item}`
                                            : item;
                                          setRecommendedTests(next);
                                        }}
                                        className="text-[10px] px-2 py-0.5 rounded-full border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition"
                                      >
                                        {item}
                                      </button>
                                    ))}
                                  </div>
                                )}
                              </div>

                              <div className="space-y-2">
                                <label className="text-xs font-semibold text-slate-600">Report Category</label>
                                <Select value={reportType} onValueChange={setReportType}>
                                  <SelectTrigger className="w-full text-sm rounded-2xl border border-slate-200 bg-white">
                                    <SelectValue placeholder="Select report category" />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="medical-report">Medical Report</SelectItem>
                                    <SelectItem value="other">Other</SelectItem>
                                  </SelectContent>
                                </Select>
                              </div>

                              <div className="space-y-2">
                                <label className="text-xs font-semibold text-slate-600">Upload Existing Report Files</label>
                                <div className="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center hover:border-sky-400 transition-colors bg-slate-50/80 relative">
                                  <input
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    multiple
                                    onChange={(e) => {
                                      const files = Array.from(e.target.files || []);
                                      setReportFiles((prev) => [...prev, ...files]);
                                    }}
                                    className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                  />
                                  <FileText className="h-8 w-8 text-slate-400 mx-auto mb-2" />
                                  <p className="text-xs font-semibold text-slate-600">Click to upload or drag & drop files</p>
                                  <p className="text-[10px] text-slate-500 mt-0.5">JPG, PNG, PDF up to 10MB each</p>
                                </div>

                                {reportFiles.length > 0 && (
                                  <div className="space-y-1.5 pt-2">
                                    <p className="text-xs font-bold text-slate-900">Selected Files ({reportFiles.length}):</p>
                                    <div className="max-h-28 overflow-y-auto space-y-1 pr-1">
                                      {reportFiles.map((file, idx) => (
                                        <div key={idx} className="flex items-center justify-between p-2 bg-white rounded-xl text-xs border border-slate-200 shadow-sm">
                                          <span className="truncate font-medium flex-1 max-w-70">{file.name}</span>
                                          <button
                                            type="button"
                                            onClick={() => setReportFiles((prev) => prev.filter((_, i) => i !== idx))}
                                            className="p-1 hover:bg-destructive/10 text-destructive rounded-lg transition-colors"
                                          >
                                            <X className="h-3.5 w-3.5" />
                                          </button>
                                        </div>
                                      ))}
                                    </div>
                                  </div>
                                )}
                              </div>
                            </div>
                          )}
                        </div>
                      )}
                    </div>

                    <PrescriptionListPanel
                      addedMedicines={addedMedicines}
                      onEditMedicine={handleEditMedicine}
                      onDeleteMedicine={handleDeleteMedicine}
                      stampPreference={stampPreference}
                      stampOptions={stampOptions}
                      onStampChange={(value) =>
                        setValue("stamp_preference", value, {
                          shouldValidate: true,
                        })
                      }
                      onFinalSubmit={handleFinalSubmit}
                      addPrescriptionPending={submittingUnified}
                      errors={
                        errors as Record<
                          string,
                          { message?: string } | undefined
                        >
                      }
                      frequencyOptions={resolvedFrequencyOptions}
                      mealOptions={resolvedMealOptions}
                      mobileTab={mobileTab}
                      generalNotes={generalNotes}
                      onGeneralNotesChange={setGeneralNotes}
                      findingsText={findingsText}
                      nextVisitDate={nextVisitDate}
                      includeReports={includeReports}
                      recommendedTests={recommendedTests}
                      reportFiles={reportFiles}
                    />
                  </div>
                </div>
              )}
            </form>
          </div>
        </DialogContent>
      </Dialog>

      <PrescriptionSuccessDialog
        open={showSuccess}
        onClose={handleSuccessClose}
      />
    </>
  );
}

function createEmptyGuidedTranscripts() {
  return {
    1: "",
    2: "",
    3: "",
    4: "",
    5: "",
  };
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

function cleanDuplicateWords(text: string): string {
  if (!text) return "";

  // 1. Standardize spacing
  let cleaned = text.replace(/\s+/g, " ").trim();

  // 2. Remove repeating consecutive patterns of length >= 5 characters (handles browser concatenation bugs)
  let prev = "";
  while (cleaned !== prev) {
    prev = cleaned;
    cleaned = cleaned.replace(/(.{5,})\s*\1/gi, "$1");
  }

  // 3. Traditional word-by-word deduplication for shorter repeating words (e.g. "the the", "no no")
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

function applyPronunciationDictionary(
  input: string,
  dictionary: Array<{ doctor_says?: string; ai_converts_to?: string }>,
): string {
  if (!input) return "";

  let normalized = input;

  for (const row of dictionary || []) {
    const from = String(row?.doctor_says || "").trim();
    const to = String(row?.ai_converts_to || "").trim();

    if (!from || !to) {
      continue;
    }

    const escaped = from.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    normalized = normalized.replace(new RegExp(`\\b${escaped}\\b`, "gi"), to);
  }

  return cleanDuplicateWords(normalized);
}

function applySpeechWordCorrections(
  input: string,
  corrections: Array<{ heard_word?: string; corrected_word?: string }>,
): string {
  if (!input) return "";

  let normalized = input;

  for (const row of corrections || []) {
    const from = String(row?.heard_word || "").trim();
    const to = String(row?.corrected_word || "").trim();

    if (!from || !to) {
      continue;
    }

    const escaped = from.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    normalized = normalized.replace(new RegExp(`\\b${escaped}\\b`, "gi"), to);
  }

  return cleanDuplicateWords(normalized);
}

function applySpeechTrainingMappings(
  input: string,
  dictionary: Array<{ doctor_says?: string; ai_converts_to?: string }>,
  corrections: Array<{ heard_word?: string; corrected_word?: string }>,
): string {
  const withPronunciation = applyPronunciationDictionary(input, dictionary);
  return applySpeechWordCorrections(withPronunciation, corrections);
}

function sanitizeClinicalText(input: string): string {
  const normalized = cleanDuplicateWords(input || "");
  if (!normalized) {
    return "";
  }

  const lines = normalized
    .split(/\r?\n+/)
    .map((line) => line.trim())
    .filter(Boolean);

  const uniqueLines: string[] = [];
  const seen = new Set<string>();

  for (const line of lines) {
    const key = line.toLowerCase();
    if (seen.has(key)) {
      continue;
    }

    seen.add(key);
    uniqueLines.push(line);
  }

  return uniqueLines.join("\n").trim();
}

function buildVoiceLanguageOptions(
  config?: AssistantConfig | null,
): Array<{ label: string; value: VoiceLocale }> {
  const locales = normalizeSupportedLocales(
    config?.supported_locales,
    config?.speech_locale,
    config?.allow_custom_locale,
  ).filter((locale) => locale.toLowerCase() !== "auto");

  const availableLocales =
    locales.length > 0 ? locales : ["en-IN", "en-US", "hi-IN", "pa-IN"];

  return availableLocales.map((locale) => ({
    label: getVoiceLocaleLabel(locale),
    value: locale,
  }));
}

function getInitialVoiceLocale(value?: string | null): VoiceLocale {
  const normalized = normalizeLocale(value);
  return normalized === "auto" ? "en-IN" : normalized;
}

function getBrowserSpeechLocale(
  selectedLocale?: string | null,
  fallbackLocale?: string | null,
): string {
  const normalized = normalizeLocale(selectedLocale);

  if (normalized !== "auto") {
    return normalized;
  }

  return getInitialVoiceLocale(fallbackLocale);
}

function normalizeSupportedLocales(
  locales?: string[] | null,
  speechLocale?: string | null,
  allowCustomLocale?: boolean,
): string[] {
  const normalized = (locales || [])
    .map((locale) => normalizeLocale(locale))
    .filter(Boolean);

  if (allowCustomLocale) {
    normalized.push(normalizeLocale(speechLocale));
  }

  const deduped = Array.from(new Set(normalized));
  return deduped.length > 0 ? deduped : ["en-IN", "en-US", "hi-IN", "pa-IN"];
}

function normalizeLocale(value?: string | null): string {
  const normalized = String(value || "").trim();
  return normalized || "en-IN";
}

function getVoiceLocaleLabel(locale: string): string {
  const normalized = locale.trim();

  return (
    {
      auto: "Auto Detect",
      "en-IN": "English (India)",
      "en-US": "English (US)",
      "hi-IN": "Hindi",
      "pa-IN": "Punjabi",
    }[normalized] ?? normalized
  );
}

function getTodayDate() {
  return new Date().toISOString().split("T")[0];
}

function toOptionItems(values?: string[] | null) {
  return (values || [])
    .map((value) => String(value || "").trim())
    .filter(Boolean)
    .map((value) => ({ label: value, value }));
}

function toFrequencyOptionItems(values?: string[] | null) {
  const labels = new Map(frequencyOptions.map((item) => [item.value, item.label]));

  return (values || [])
    .map((value) => String(value || "").trim())
    .filter(Boolean)
    .map((value) => ({
      label: labels.get(value) || value,
      value,
    }));
}

function toMealOptionItems(values?: string[] | null) {
  const labels = new Map(mealOptions.map((item) => [item.value, item.label]));

  return (values || [])
    .map((value) => String(value || "").trim())
    .filter(Boolean)
    .map((value) => ({
      label: labels.get(value) || value.replace(/_/g, " "),
      value,
    }));
}

function normalizeFieldRules(values?: string[] | null) {
  return (values || [])
    .map((value) => String(value || "").trim())
    .filter(Boolean)
    .map((value) => (value === "application" ? "application_area" : value));
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

function combineSummaryParts(parts: Array<string | null | undefined>) {
  return parts
    .map((part) => (part || "").trim())
    .filter(Boolean)
    .join(" | ");
}

function normalizeSearchText(text: string) {
  return text
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, " ")
    .trim();
}

function extractMedicineSearchCandidate(text: string) {
  return text
    .replace(
      /\b(?:please|add|medicine|name|prescribe|start|give|patient|take|tab|tablet|capsule|cap|syrup|drop|drops|injection|cream|ointment)\b/gi,
      " ",
    )
    .replace(/\s+/g, " ")
    .trim();
}

function resolveMedicineShortcut(
  input: string,
  shortcuts: Array<{ medicine?: string; shortcut?: string }>,
) {
  const candidate = String(input || "").trim();
  if (!candidate) {
    return "";
  }

  const normalizedCandidate = candidate.toLowerCase();
  const matched = (shortcuts || []).find((item) => {
    const shortcut = String(item?.shortcut || "").trim().toLowerCase();
    return shortcut !== "" && shortcut === normalizedCandidate;
  });

  return String(matched?.medicine || candidate).trim();
}

function findMedicineMatch(list: MedicineItem[], query: string) {
  const normalizedQuery = normalizeSearchText(query);

  return (
    list.find(
      (medicine) => normalizeSearchText(medicine.name) === normalizedQuery,
    ) ||
    list.find((medicine) =>
      normalizeSearchText(medicine.name).includes(normalizedQuery),
    ) ||
    list.find((medicine) =>
      normalizedQuery.includes(normalizeSearchText(medicine.name)),
    ) ||
    null
  );
}

function parseMedicationTypeFromSpeech(text: string) {
  const value = text.trim().toLowerCase();

  if (/\b(?:tablet|tab)\b/.test(value)) return "tablet";
  if (/\b(?:capsule|cap)\b/.test(value)) return "capsule";
  if (/\b(?:syrup|syp|liquid|suspension)\b/.test(value)) return "syrup";
  if (/\b(?:drop|drops|eye drop|ear drop)\b/.test(value)) return "drop";
  if (/\b(?:injection|inj)\b/.test(value)) return "injection";
  if (/\bcream\b/.test(value)) return "cream";
  if (/\bointment\b/.test(value)) return "ointment";

  return "";
}

function parseStrengthFromSpeech(text: string): string {
  const match = text.match(/\b(\d+(?:\.\d+)?\s*(?:mg|g|mcg|ml|percentage|%)(?:\/\d+\s*(?:ml|mg))?)\b/i);
  return match ? match[1] : "";
}

function getMedicationTypeLabel(value: string) {
  return (
    {
      tablet: "Tablet",
      capsule: "Capsule",
      syrup: "Syrup",
      drop: "Drop",
      injection: "Injection",
      cream: "Cream",
      ointment: "Ointment",
      other: "Other",
    }[value] ?? value
  );
}

function parseDosageFromSpeech(text: string, type?: string | null): string {
  const value = text.trim().toLowerCase();
  const medType = (type || "").trim().toLowerCase();

  if (!value) {
    return "";
  }

  if (medType === "tablet" || medType === "capsule") {
    if (/\b(?:half|0\.5|1\/2)\b/.test(value)) return "0.5 tablet";
    if (/\b(?:1\.5|one and a half)\b/.test(value)) return "1.5 tablets";
    if (/\b(?:3|three)\b/.test(value)) return "3 tablets";
    if (/\b(?:2|two)\b/.test(value)) return "2 tablets";
    if (/\b(?:1|one|single)\b/.test(value)) return "1 tablet";
    return "";
  }

  if (medType === "syrup") {
    if (/(?:\b2\.5\b|half spoon)/.test(value)) return "2.5 ml";
    if (/(?:\b20\b|four spoon)/.test(value)) return "20 ml";
    if (/(?:\b15\b|three spoon)/.test(value)) return "15 ml";
    if (/(?:\b10\b|two spoon)/.test(value)) return "10 ml";
    if (/(?:\b5\b|one spoon|1 spoon)/.test(value)) return "5 ml";
    return "";
  }

  if (medType === "drop") {
    if (/\b(?:4|four)\b/.test(value)) return "4 drops";
    if (/\b(?:3|three)\b/.test(value)) return "3 drops";
    if (/\b(?:2|two)\b/.test(value)) return "2 drops";
    if (/\b(?:1|one)\b/.test(value)) return "1 drop";
    return "";
  }

  if (medType === "cream" || medType === "ointment") {
    if (/pea/.test(value)) return "pea-sized amount";
    if (/(?:thin|layer)/.test(value)) return "thin layer";
    return "";
  }

  if (medType === "injection") {
    if (/\b(?:2|two)\b/.test(value)) return "2 units";
    if (/\b(?:1|one)\b/.test(value)) return "1 unit";
  }

  return "";
}

function parseFrequencyFromSpeech(text: string) {
  const value = text.trim().toLowerCase();

  if (!value) {
    return "";
  }

  if (
    /\bod\b/.test(value) ||
    /\bonce a day\b/.test(value) ||
    /\bonce daily\b/.test(value)
  ) {
    return "OD";
  }

  if (
    /\bbd\b/.test(value) ||
    /\btwice a day\b/.test(value) ||
    /\btwice daily\b/.test(value)
  ) {
    return "BD";
  }

  if (
    /\btds\b/.test(value) ||
    /\bthree times\b/.test(value) ||
    /\bthrice\b/.test(value)
  ) {
    return "TDS";
  }

  if (
    /\bsos\b/.test(value) ||
    /\bas needed\b/.test(value) ||
    /\bwhen needed\b/.test(value)
  ) {
    return "SOS";
  }

  return "";
}

function getFrequencyLabel(value: string) {
  return (
    {
      OD: "Once a day",
      BD: "Twice a day",
      TDS: "Three times a day",
      SOS: "SOS",
    }[value] ?? value
  );
}

function parseTimingFlagsFromSpeech(text: string) {
  const value = text.trim().toLowerCase();

  return {
    timing_morning: /\bmorning\b/.test(value),
    timing_afternoon: /\b(?:afternoon|noon)\b/.test(value),
    timing_evening: /\bevening\b/.test(value),
    timing_night: /\b(?:night|bedtime)\b/.test(value),
  };
}

function countActiveTimings(flags: {
  timing_morning: boolean;
  timing_afternoon: boolean;
  timing_evening: boolean;
  timing_night: boolean;
}) {
  return Object.values(flags).filter(Boolean).length;
}

function inferFrequencyFromTimings(flags: {
  timing_morning: boolean;
  timing_afternoon: boolean;
  timing_evening: boolean;
  timing_night: boolean;
}) {
  const count = countActiveTimings(flags);

  if (count <= 0) return "";
  if (count === 1) return "OD";
  if (count === 2) return "BD";
  return "TDS";
}

function applyFrequencyDefaults(
  frequency: string,
  flags: {
    timing_morning: boolean;
    timing_afternoon: boolean;
    timing_evening: boolean;
    timing_night: boolean;
  },
) {
  if (countActiveTimings(flags) > 0) {
    return flags;
  }

  if (frequency === "OD") {
    return {
      timing_morning: false,
      timing_afternoon: false,
      timing_evening: false,
      timing_night: true,
    };
  }

  if (frequency === "BD") {
    return {
      timing_morning: true,
      timing_afternoon: false,
      timing_evening: false,
      timing_night: true,
    };
  }

  if (frequency === "TDS") {
    return {
      timing_morning: true,
      timing_afternoon: true,
      timing_evening: false,
      timing_night: true,
    };
  }

  return flags;
}

function getTimingLabels(flags: {
  timing_morning: boolean;
  timing_afternoon: boolean;
  timing_evening: boolean;
  timing_night: boolean;
}) {
  return [
    flags.timing_morning ? "Morning" : "",
    flags.timing_afternoon ? "Afternoon" : "",
    flags.timing_evening ? "Evening" : "",
    flags.timing_night ? "Night" : "",
  ].filter(Boolean);
}

function parseMealRelationFromSpeech(
  text: string,
): PrescriptionForm["meal"] | "" {
  const value = text.trim().toLowerCase();

  if (/\b(?:before meal|before food|empty stomach|ac)\b/.test(value)) {
    return "before_meal";
  }

  if (/\b(?:after meal|after food|post meal|pc)\b/.test(value)) {
    return "after_meal";
  }

  if (/\b(?:with meal|with food|during meal)\b/.test(value)) {
    return "with_meal";
  }

  return "";
}

function getMealLabel(value: PrescriptionForm["meal"]) {
  return (
    {
      before_meal: "Before Meal",
      after_meal: "After Meal",
      with_meal: "With Meal",
    }[value] ?? value
  );
}

function parseDurationFromSpeech(text: string, currentStartDate: string) {
  const match = text
    .toLowerCase()
    .match(
      /\b(?:for\s+)?(\d+|one|two|three|four|five|six|seven|eight|nine|ten)\s+(day|days|week|weeks|month|months|night|nights)\b/,
    );

  if (!match) {
    return null;
  }

  const amount = parseCountToken(match[1]);
  const unit = match[2];

  if (!amount) {
    return null;
  }

  const days = unit.startsWith("week")
    ? amount * 7
    : unit.startsWith("month")
      ? amount * 30
      : amount;

  const startDate = currentStartDate || getTodayDate();
  const endDate = addDaysToDate(startDate, Math.max(days - 1, 0));

  return {
    startDate,
    endDate,
    label: `${amount} ${unit.endsWith("s") ? unit : `${unit}${amount > 1 ? "s" : ""}`}`,
  };
}

function parseCountToken(token: string) {
  const normalized = token.trim().toLowerCase();

  if (/^\d+$/.test(normalized)) {
    return Number(normalized);
  }

  return (
    {
      one: 1,
      two: 2,
      three: 3,
      four: 4,
      five: 5,
      six: 6,
      seven: 7,
      eight: 8,
      nine: 9,
      ten: 10,
    }[normalized] ?? 0
  );
}

function addDaysToDate(dateString: string, days: number) {
  const date = new Date(dateString);
  date.setDate(date.getDate() + days);
  return date.toISOString().split("T")[0];
}

function extractVoiceInstructions(text: string) {
  return text
    .replace(
      /\b(before meal|before food|after meal|after food|with meal|with food|empty stomach|post meal|during meal|ac|pc)\b/gi,
      " ",
    )
    .replace(
      /\b(?:for\s+)?(\d+|one|two|three|four|five|six|seven|eight|nine|ten)\s+(day|days|week|weeks|month|months|night|nights)\b/gi,
      " ",
    )
    .replace(/\s+/g, " ")
    .replace(/^[,.;:\-\s]+|[,.;:\-\s]+$/g, "")
    .trim();
}

function parseApplicationAreaFromSpeech(text: string, options: string[]): string {
  const value = text.trim().toLowerCase();
  if (!value || !options || options.length === 0) {
    return "";
  }

  const sortedOptions = [...options].sort((a, b) => b.length - a.length);

  for (const option of sortedOptions) {
    const escapedOption = option.replace(/[-\/\\^$*+?.()|[\]{}]/g, "\\$&");
    const regex = new RegExp(`\\b${escapedOption}\\b`, "i");
    if (regex.test(value)) {
      return option;
    }
  }

  return "";
}

function extractFollowUpNote(text: string): string {
  const value = text.trim();
  const match = value.match(/\b(?:follow\s*up|follow-up)(?:\s*(?:in|after|:|note|with))?\s+(.+)$/i);
  if (match) {
    return match[1].trim();
  }
  return "";
}


