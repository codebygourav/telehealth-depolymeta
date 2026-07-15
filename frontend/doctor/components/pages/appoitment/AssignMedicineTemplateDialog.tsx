"use client";

import { useMemo, useState, useEffect } from "react";
import { Calendar, CheckCircle2, Loader2, Pill, Search, Volume2, VolumeX, Languages } from "lucide-react";
import { toast } from "sonner";
import { cn } from "@/lib/utils";
import {
  SpeechLanguage,
  speakText,
  stopSpeaking,
  generateMedicineSpeechText,
} from "@/lib/medicineVoiceHelper";

import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useAssignMedicineTemplate } from "@/mutations/useAssignMedicineTemplate";
import { useDoctorProfile } from "@/queries/useProfile";
import { useMedicineTemplates } from "@/queries/useMedicineTemplates";
import type {
  MedicineTemplate,
  MedicineTemplateItem,
} from "@/types/medicine-template";

interface AssignMedicineTemplateDialogProps {
  appointmentId: string;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export default function AssignMedicineTemplateDialog({
  appointmentId,
  open,
  onOpenChange,
}: AssignMedicineTemplateDialogProps) {
  const { data: profileResponse } = useDoctorProfile();
  const voiceSettings = profileResponse?.data?.voice_settings;

  const [selectedTemplateId, setSelectedTemplateId] = useState<string>("");
  const [search, setSearch] = useState("");
  const [startDate, setStartDate] = useState(getTodayDate());
  const [stampPreference, setStampPreference] = useState<
    "only_global" | "only_department" | "both"
  >("only_global");

  const [language, setLanguage] = useState<SpeechLanguage>("en");
  const [isPlayingAll, setIsPlayingAll] = useState(false);
  const [currentPlayingIndex, setCurrentPlayingIndex] = useState<number | null>(null);

  useEffect(() => {
    return () => {
      stopSpeaking();
    };
  }, []);

  // Stop active speech if dialog closes or selected template changes
  useEffect(() => {
    stopSpeaking();
    setIsPlayingAll(false);
    setCurrentPlayingIndex(null);
  }, [open, selectedTemplateId]);

  const handleLanguageChange = (lang: SpeechLanguage) => {
    setLanguage(lang);
    stopSpeaking();
    setIsPlayingAll(false);
    setCurrentPlayingIndex(null);
  };

  const playAll = () => {
    if (isPlayingAll) {
      stopSpeaking();
      setIsPlayingAll(false);
      setCurrentPlayingIndex(null);
      return;
    }

    const items = selectedTemplate?.items ?? [];
    if (items.length === 0) return;

    setIsPlayingAll(true);
    setCurrentPlayingIndex(0);

    const firstMedText = generateMedicineSpeechText(items[0], language);
    speakNext(firstMedText, 0);
  };

  const speakNext = (text: string, index: number) => {
    const items = selectedTemplate?.items ?? [];
    speakText(
      text,
      language,
      undefined,
      () => {
        if (index < items.length - 1) {
          const nextIndex = index + 1;
          setCurrentPlayingIndex(nextIndex);
          const nextMedText = generateMedicineSpeechText(items[nextIndex], language);
          speakNext(nextMedText, nextIndex);
        } else {
          setIsPlayingAll(false);
          setCurrentPlayingIndex(null);
        }
      },
      (err) => {
        console.error(err);
        setIsPlayingAll(false);
        setCurrentPlayingIndex(null);
      },
      voiceSettings
    );
  };

  const templatesQuery = useMedicineTemplates();
  const assignTemplate = useAssignMedicineTemplate(appointmentId);

  const templates = useMemo(
    () => templatesQuery.data?.data ?? [],
    [templatesQuery.data?.data],
  );

  const filteredTemplates = useMemo(() => {
    const query = search.trim().toLowerCase();

    if (!query) {
      return templates;
    }

    return templates.filter((template) => {
      const itemNames =
        template.items?.map((item) => item.medicine_name).join(" ") ?? "";

      return `${template.name} ${template.description ?? ""} ${itemNames}`
        .toLowerCase()
        .includes(query);
    });
  }, [search, templates]);

  const selectedTemplate =
    templates.find((template) => template.id === selectedTemplateId) ?? null;

  const handleAssign = () => {
    if (!selectedTemplateId) {
      toast.error("Please select a medicine template.");
      return;
    }

    assignTemplate.mutate(
      {
        template_id: selectedTemplateId,
        start_date: startDate,
        stamp_preference: stampPreference,
      },
      {
        onSuccess: () => {
          toast.success("Medicine template assigned to this prescription.");
          setSelectedTemplateId("");
          setSearch("");
          setStartDate(getTodayDate());
          onOpenChange(false);
        },
        onError: (error: unknown) => {
          toast.error(getErrorMessage(error));
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="w-[95vw] max-w-4xl! p-0 overflow-hidden rounded-xl sm:rounded-2xl">
        <DialogHeader className="border-b px-4 sm:px-6 py-3 sm:py-4">
          <DialogTitle className="text-base sm:text-lg md:text-xl">
            Use Medicine Template
          </DialogTitle>
        </DialogHeader>

        <div className="grid max-h-[82vh] overflow-hidden md:grid-cols-[minmax(0,1fr)_minmax(280px,360px)]">
          <div className="min-h-0 overflow-y-auto border-b p-4 sm:p-5 md:border-b-0 md:border-r">
            <div className="relative mb-4">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search templates or medicines..."
                className="h-10 pl-9 text-sm"
              />
            </div>

            {templatesQuery.isLoading ? (
              <div className="flex items-center justify-center py-10 text-sm text-muted-foreground">
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Loading templates...
              </div>
            ) : templatesQuery.error ? (
              <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                Failed to load medicine templates.
              </div>
            ) : filteredTemplates.length === 0 ? (
              <div className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                No active medicine templates found.
              </div>
            ) : (
              <div className="space-y-3">
                {filteredTemplates.map((template) => (
                  <TemplateButton
                    key={template.id}
                    template={template}
                    selected={template.id === selectedTemplateId}
                    onClick={() => setSelectedTemplateId(template.id)}
                  />
                ))}
              </div>
            )}
          </div>

          <div className="min-h-0 overflow-y-auto p-4 sm:p-5">
            <div className="space-y-4">
              <div>
                <Label className="text-xs sm:text-sm">Start Date</Label>
                <div className="relative mt-1.5">
                  <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                  <Input
                    type="date"
                    min={getTodayDate()}
                    value={startDate}
                    onChange={(event) => setStartDate(event.target.value)}
                    className="h-10 pl-9 text-sm"
                  />
                </div>
              </div>

              <div>
                <Label className="text-xs sm:text-sm">Stamp Preference</Label>
                <Select
                  value={stampPreference}
                  onValueChange={(value) =>
                    setStampPreference(
                      value as "only_global" | "only_department" | "both",
                    )
                  }
                >
                  <SelectTrigger className="mt-1.5 h-10 text-sm">
                    <SelectValue placeholder="Select stamp" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="only_global">Global Stamp</SelectItem>
                    <SelectItem value="only_department">
                      Department Stamp
                    </SelectItem>
                    <SelectItem value="both">Both Stamps</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="rounded-lg border bg-muted/20 p-3">
                {selectedTemplate ? (
                  <div className="space-y-3">
                    <div>
                      <div className="flex items-start justify-between gap-3">
                        <h3 className="text-sm font-semibold">
                          {selectedTemplate.name}
                        </h3>
                        <Badge
                          variant={
                            selectedTemplate.scope_type === "global"
                              ? "secondary"
                              : "outline"
                          }
                        >
                          {scopeLabel(selectedTemplate)}
                        </Badge>
                      </div>
                      {selectedTemplate.description && (
                        <p className="mt-1 text-xs text-muted-foreground">
                          {selectedTemplate.description}
                        </p>
                      )}

                      {/* Voice Announcement Preview Panel */}
                      <div className="flex flex-wrap items-center justify-between gap-3 p-2 bg-muted/60 rounded-lg border text-[11px] mt-2">
                        <div className="flex items-center gap-1.5 font-bold uppercase tracking-wider text-muted-foreground">
                          <Languages className="w-3.5 h-3.5 text-primary" />
                          <span>Voice Lang</span>
                        </div>
                        <div className="flex items-center gap-0.5 bg-background p-0.5 rounded border border-input">
                          {(
                            [
                              { key: 'en', label: 'EN' },
                              { key: 'hi', label: 'HI' },
                              { key: 'pa', label: 'PA' },
                            ] as const
                          ).map((lang) => (
                            <button
                              key={lang.key}
                              type="button"
                              onClick={() => handleLanguageChange(lang.key)}
                              className={cn(
                                "px-2 py-0.5 text-[10px] font-bold rounded cursor-pointer transition-all border-none outline-none",
                                language === lang.key
                                  ? "bg-primary text-primary-foreground shadow-sm"
                                  : "text-muted-foreground hover:bg-muted/80"
                              )}
                            >
                              {lang.label}
                            </button>
                          ))}
                        </div>
                        
                        <Button
                          type="button"
                          onClick={playAll}
                          variant="outline"
                          className="h-7 text-[10px] font-bold py-1 px-2.5 m-0 rounded border-none shadow-sm cursor-pointer"
                        >
                          {isPlayingAll ? (
                            <>
                              <VolumeX className="w-3.5 h-3.5 mr-1" />
                              Stop
                            </>
                          ) : (
                            <>
                              <Volume2 className="w-3.5 h-3.5 mr-1" />
                              Listen All
                            </>
                          )}
                        </Button>
                      </div>
                    </div>

                    <div className="space-y-2">
                      {selectedTemplate.items?.map((item, index) => (
                        <TemplateMedicinePreview
                          key={item.id}
                          item={item}
                          index={index + 1}
                          language={language}
                          isCurrentlySpeaking={isPlayingAll && currentPlayingIndex === index}
                          voiceSettings={voiceSettings}
                        />
                      ))}
                    </div>
                  </div>
                ) : (
                  <div className="py-8 text-center text-sm text-muted-foreground">
                    Select a template to preview medicines.
                  </div>
                )}
              </div>

              <Button
                type="button"
                className="w-full"
                disabled={!selectedTemplateId || assignTemplate.isPending}
                onClick={handleAssign}
              >
                {assignTemplate.isPending ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Assigning...
                  </>
                ) : (
                  <>
                    <CheckCircle2 className="mr-2 h-4 w-4" />
                    Assign Template
                  </>
                )}
              </Button>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

function TemplateButton({
  template,
  selected,
  onClick,
}: {
  template: MedicineTemplate;
  selected: boolean;
  onClick: () => void;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`w-full rounded-lg border p-3 text-left transition-colors ${
        selected ? "border-primary bg-primary/5" : "hover:bg-muted/40"
      }`}
    >
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <div className="flex items-center gap-2">
            <Pill className="h-4 w-4 shrink-0 text-primary" />
            <h3 className="truncate text-sm font-semibold">{template.name}</h3>
          </div>
          {template.description && (
            <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
              {template.description}
            </p>
          )}
        </div>
        <Badge
          variant={template.scope_type === "global" ? "secondary" : "outline"}
          className="shrink-0 text-[10px]"
        >
          {scopeLabel(template)}
        </Badge>
      </div>
      <div className="mt-2 flex flex-wrap gap-1.5">
        {(template.items ?? []).slice(0, 3).map((item) => (
          <Badge key={item.id} variant="outline" className="text-[10px]">
            {item.medicine_name} · {item.doses_per_day ?? 1}x/day
          </Badge>
        ))}
        {(template.items?.length ?? 0) > 3 && (
          <Badge variant="outline" className="text-[10px]">
            +{(template.items?.length ?? 0) - 3} more
          </Badge>
        )}
      </div>
    </button>
  );
}

function TemplateMedicinePreview({
  item,
  index,
  language,
  isCurrentlySpeaking,
  voiceSettings,
}: {
  item: MedicineTemplateItem;
  index: number;
  language: SpeechLanguage;
  isCurrentlySpeaking: boolean;
  voiceSettings?: {
    voice_name?: string | null;
    speech_rate?: number;
    speech_pitch?: number;
    speech_locale?: string | null;
  };
}) {
  const [isSpeakingSelf, setIsSpeakingSelf] = useState(false);
  const isSpeaking = isCurrentlySpeaking || isSpeakingSelf;

  const toggleSpeak = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();

    if (isSpeakingSelf) {
      stopSpeaking();
      setIsSpeakingSelf(false);
    } else {
      stopSpeaking();
      setIsSpeakingSelf(true);
      
      const text = generateMedicineSpeechText(item, language);
      speakText(
        text,
        language,
        undefined,
        () => setIsSpeakingSelf(false),
        () => setIsSpeakingSelf(false),
        voiceSettings
      );
    }
  };

  useEffect(() => {
    return () => {
      if (isSpeakingSelf) stopSpeaking();
    };
  }, [isSpeakingSelf]);

  return (
    <div className={cn(
      "rounded-md border p-3 bg-background transition-all",
      isSpeaking ? "border-emerald-600/30 bg-emerald-50/10 shadow-sm" : "border-muted"
    )}>
      <div className="flex items-start justify-between gap-2">
        <div>
          <div className="flex items-center gap-1.5">
            <p className="text-sm font-medium">
              {index}. {item.medicine_name}
            </p>
            <button
              type="button"
              onClick={toggleSpeak}
              className={cn(
                "p-1 rounded-full border border-muted-foreground/10 transition-all cursor-pointer flex items-center justify-center outline-none",
                isSpeaking 
                  ? "bg-[#013220] text-white hover:bg-emerald-800" 
                  : "bg-white text-muted-foreground hover:bg-muted"
              )}
              title={isSpeaking ? "Stop voice guidance" : "Listen to voice guidance"}
            >
              {isSpeaking ? (
                <VolumeX className="w-3.5 h-3.5 animate-pulse" />
              ) : (
                <Volume2 className="w-3.5 h-3.5" />
              )}
            </button>
          </div>
          <p className="text-xs text-muted-foreground mt-1">
            {[
              item.dosage,
              `${item.doses_per_day ?? 1}x/day`,
              frequencyLabel(item.frequency),
              item.frequency_times?.map(formatTime).join(", "),
            ]
              .filter(Boolean)
              .join(" • ")}
          </p>
        </div>
        {item.medicine_type && (
          <Badge variant="outline" className="text-[10px]">
            {item.medicine_type}
          </Badge>
        )}
      </div>
      <div className="mt-2 text-xs text-muted-foreground">
        {durationLabel(item)}
        {item.first_dose_time
          ? ` • first dose ${formatTime(item.first_dose_time)}`
          : ""}
        {item.dose_interval_hours
          ? ` • every ${item.dose_interval_hours}h`
          : ""}
        {item.meal_timing ? ` • ${mealLabel(item.meal_timing)}` : ""}
      </div>
      {item.instructions && (
        <p className="mt-2 rounded bg-blue-50 p-2 text-xs text-blue-800">
          {item.instructions}
        </p>
      )}
    </div>
  );
}

function frequencyLabel(frequency: string) {
  const labels: Record<string, string> = {
    OD: "Once a day",
    BD: "Twice a day",
    TDS: "Three times a day",
    SOS: "As needed",
  };

  return labels[frequency] ?? frequency;
}

function mealLabel(meal: string) {
  return meal
    .replaceAll("_", " ")
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function durationLabel(item: MedicineTemplateItem) {
  if (!item.duration_value) {
    return "No end date";
  }

  return `${item.duration_value} ${item.duration_type ?? "days"}`;
}

function scopeLabel(template: MedicineTemplate) {
  switch (template.scope_type) {
    case "doctor":
      return "Doctor";
    case "department":
      return "Department";
    default:
      return "Global";
  }
}

function formatTime(time: string) {
  const [hour = "0", minute = "00"] = time.split(":");
  const date = new Date();
  date.setHours(Number(hour), Number(minute), 0, 0);

  return date.toLocaleTimeString("en-US", {
    hour: "numeric",
    minute: "2-digit",
  });
}

function getTodayDate() {
  return new Date().toISOString().split("T")[0];
}

function getErrorMessage(error: unknown) {
  if (typeof error === "object" && error !== null && "response" in error) {
    const response = (
      error as {
        response?: {
          data?: {
            errors?: { message?: string };
            message?: string;
          };
        };
      }
    ).response;

    return (
      response?.data?.errors?.message ||
      response?.data?.message ||
      "Failed to assign medicine template."
    );
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Failed to assign medicine template.";
}
