"use client";

import AddConclusionDialog from "@/components/pages/appoitment/AddConclusionDialog";
import AddPrescriptionDialog from "@/components/pages/appoitment/AddPrescriptionDialog";
import AssignMedicineTemplateDialog from "@/components/pages/appoitment/AssignMedicineTemplateDialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { useConclusionByAppointmentId } from "@/queries/useConclusionByAppointmentId";
import { usePrescriptionByAppointmentId } from "@/queries/usePrescriptionByAppointmentId";
import { useDeletePrescriptionItem } from "@/queries/useDeletePrescriptionItem";
import { getStatusColor } from "@/src/utils/getStatusColor";
import {
  AlertCircle,
  Calendar,
  ChevronDown,
  ChevronUp,
  ClipboardList,
  Clock,
  Download,
  ExternalLink,
  FileText,
  Mic,
  Trash2,
  Pencil,
} from "lucide-react";
import { useCallback, useState } from "react";

// TypeScript interfaces
interface Medicine {
  prescription_id: string;
  name: string;
  type: string;
  status: string;
  dosage: string;
  frequencylabel: string;
  times: string;
  meal: string;
  date: string;
  instructions?: string[];
  notes?: string;
  use_type?: string;
  take_when?: string;
  min_gap?: string;
  max_doses_per_day?: string;
  patient_instruction?: string;
  medicine_source?: "inventory" | "doctor_added" | "unknown";
  created_via?: "speech" | null;
}

type DraftHistoryItem = {
  id: string;
  source_type?: "text" | "speech" | string;
  status?: string;
  input_text?: string;
  confidence_score?: number | null;
  warnings?: string[];
  missing_fields?: string[];
  applied_at?: string | null;
  medicine_name?: string | null;
  medicine_source?: "inventory" | "doctor_added" | "unknown" | null;
  created_medicines?: Array<{
    prescription_id?: string;
    medicine_name?: string;
    medicine_source?: "inventory" | "doctor_added" | "unknown";
  }>;
};

type MealTiming = "before_meal" | "after_meal" | "with_meal" | string;
type DictationAssistantConfig = {
  enabled?: boolean;
  input_mode?: string;
  text_mode_max_chars?: number;
  speech_locale?: string;
  supported_locales?: string[];
  allow_custom_locale?: boolean;
  requires_doctor_review?: boolean;
  browser_speech_enabled?: boolean;
};
type ConclusionReportFile = {
  id: string;
  name: string;
  url: string;
  type: string;
};

// Accordion Item Component
const MedicineAccordionItem = ({
  medicine,
  index,
  onDelete,
  onEdit,
}: {
  medicine: Medicine;
  index: number;
  onDelete: (medicine: Medicine) => void;
  onEdit: (medicine: Medicine) => void;
}) => {
  const [isOpen, setIsOpen] = useState(false);

  const getMealLabel = useCallback((meal: MealTiming): string => {
    switch (meal) {
      case "before_meal":
        return "Before Meal";
      case "after_meal":
        return "After Meal";
      case "with_meal":
        return "With Meal";
      default:
        return meal || "Not specified";
    }
  }, []);

  const toggleOpen = useCallback(() => {
    setIsOpen((prev) => !prev);
  }, []);

  return (
    <Card className="overflow-hidden border border-muted hover:border-primary/40 hover:shadow-md transition-all duration-300 rounded-2xl bg-linear-to-br from-background to-muted/10">
      <div
        className="cursor-pointer hover:bg-muted/20 transition-colors"
        onClick={toggleOpen}
        role="button"
        aria-expanded={isOpen}
        aria-controls={`medicine-details-${medicine.prescription_id}`}
      >
        <CardHeader className="p-4 sm:p-5">
          <div className="flex items-center justify-between gap-3">
            <div className="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
              <div className="p-2 sm:p-2.5 rounded-xl bg-primary/10 shrink-0 border border-primary/20 shadow-sm">
                <span className="text-xs sm:text-sm font-bold text-primary">
                  #{index}
                </span>
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                  <h4 className="font-bold text-sm sm:text-base text-foreground tracking-tight truncate">
                    {medicine.name}
                  </h4>
                  <Badge
                    variant="secondary"
                    className="text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-md"
                  >
                    {medicine.type}
                  </Badge>
                  <Badge
                    className={`${getStatusColor("session", medicine.status)} text-[10px] sm:text-xs font-semibold px-2 py-0.5 rounded-md`}
                  >
                    {medicine.status}
                  </Badge>
                  {medicine.medicine_source === "doctor_added" && (
                    <Badge className="bg-amber-50 text-amber-700 hover:bg-amber-50 border border-amber-200 text-[10px] sm:text-xs px-2 py-0.5 rounded-md">
                      Doctor-added
                    </Badge>
                  )}
                  {medicine.medicine_source === "inventory" && (
                    <Badge
                      variant="outline"
                      className="border-primary/20 text-primary bg-primary/5 text-[10px] sm:text-xs px-2 py-0.5 rounded-md font-semibold"
                    >
                      Stock medicine
                    </Badge>
                  )}
                  {medicine.created_via === "speech" && (
                    <Badge className="bg-blue-50 text-blue-700 hover:bg-blue-50 border border-blue-200 text-[10px] sm:text-xs px-2 py-0.5 rounded-md">
                      Voice draft
                    </Badge>
                  )}
                </div>
                <div className="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-1.5 text-xs text-muted-foreground font-medium">
                  <span className="text-foreground font-semibold">{medicine.dosage}</span>
                  <span className="text-muted-foreground/60">•</span>
                  <span className="capitalize">
                    {medicine.use_type === "sos"
                      ? "SOS (As Needed)"
                      : medicine.use_type && medicine.use_type !== "regular"
                        ? medicine.use_type.replace("_", " ")
                        : medicine.frequencylabel}
                  </span>
                  {medicine.use_type !== "sos" && medicine.times && (
                    <>
                      <span className="text-muted-foreground/60">•</span>
                      <span>{medicine.times}</span>
                    </>
                  )}
                </div>
              </div>
            </div>

            <div className="flex items-center gap-1 sm:gap-2 shrink-0">

              <Button
                variant="ghost"
                size="icon"
                className="h-9 w-9 text-destructive hover:text-destructive hover:bg-destructive/10 rounded-xl shrink-0"
                onClick={(e) => {
                  e.stopPropagation();
                  onDelete(medicine);
                }}
              >
                <Trash2 className="h-4.5 w-4.5" />
              </Button>
              {isOpen ? (
                <ChevronUp className="h-5 w-5 text-muted-foreground shrink-0" />
              ) : (
                <ChevronDown className="h-5 w-5 text-muted-foreground shrink-0" />
              )}
            </div>
          </div>
        </CardHeader>
      </div>

      {isOpen && (
        <CardContent
          className="p-4 sm:p-5 pt-0 border-t bg-muted/5"
          id={`medicine-details-${medicine.prescription_id}`}
        >
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            {/* Column 1: Intake & Dosage Details */}
            <div className="p-3.5 bg-background border border-muted rounded-xl space-y-3">
              <p className="text-[10px] font-bold text-primary uppercase tracking-wider">Intake & Dosing</p>
              <div className="space-y-2 text-xs">
                <div>
                  <span className="text-muted-foreground block text-[10px] uppercase">Meal Relation</span>
                  <span className="font-semibold text-foreground">{getMealLabel(medicine.meal)}</span>
                </div>
                {medicine.use_type && medicine.use_type !== "regular" && medicine.use_type !== "sos" && (
                  <div>
                    <span className="text-muted-foreground block text-[10px] uppercase">Special Instructions</span>
                    <span className="font-semibold text-foreground capitalize">Take as {medicine.use_type.replace("_", " ")}</span>
                  </div>
                )}
              </div>
            </div>

            {/* Column 2: Duration & Origin */}
            <div className="p-3.5 bg-background border border-muted rounded-xl space-y-3">
              <p className="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">Duration & Origin</p>
              <div className="space-y-2 text-xs">
                <div>
                  <span className="text-muted-foreground block text-[10px] uppercase">Duration</span>
                  <span className="font-semibold text-foreground">{medicine.date}</span>
                </div>
                <div>
                  <span className="text-muted-foreground block text-[10px] uppercase">Source</span>
                  <span className="font-semibold text-foreground">
                    {medicine.medicine_source === "doctor_added"
                      ? "Doctor-added medicine"
                      : medicine.medicine_source === "inventory"
                        ? "Stock medicine"
                        : "Unknown"}
                  </span>
                </div>
                <div>
                  <span className="text-muted-foreground block text-[10px] uppercase">Entry Method</span>
                  <span className="font-semibold text-foreground">
                    {medicine.created_via === "speech" ? "Voice dictation" : "Standard entry"}
                  </span>
                </div>
              </div>
            </div>

            {/* Column 3: Instructions & Custom Notes */}
            <div className="p-3.5 bg-background border border-muted rounded-xl space-y-3">
              <p className="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Instructions & SOS Rules</p>
              <div className="space-y-2 text-xs">
                {medicine.use_type === "sos" && (
                  <div className="space-y-1.5 border-b pb-2 mb-2">
                    {medicine.take_when && (
                      <div>
                        <span className="text-muted-foreground block text-[10px] uppercase">SOS Criteria</span>
                        <span className="font-semibold text-foreground capitalize">{medicine.take_when}</span>
                      </div>
                    )}
                    {medicine.min_gap && (
                      <div>
                        <span className="text-muted-foreground block text-[10px] uppercase">Min Gap</span>
                        <span className="font-semibold text-foreground capitalize">{medicine.min_gap}</span>
                      </div>
                    )}
                    {medicine.max_doses_per_day && (
                      <div>
                        <span className="text-muted-foreground block text-[10px] uppercase">Max Doses Per Day</span>
                        <span className="font-semibold text-foreground capitalize">{medicine.max_doses_per_day}</span>
                      </div>
                    )}
                  </div>
                )}
                {medicine.instructions && medicine.instructions.length > 0 && (
                  <div>
                    <span className="text-muted-foreground block text-[10px] uppercase mb-1">Standard Instructions</span>
                    <ul className="list-disc list-inside space-y-0.5 text-blue-700 font-medium bg-blue-50/50 p-2 rounded-lg border border-blue-100">
                      {medicine.instructions.map((ins: string, i: number) => (
                        <li key={`${medicine.prescription_id}-instruction-${i}`} className="text-[11px] truncate">
                          {ins}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
                {medicine.notes && (
                  <div className="pt-1">
                    <span className="text-muted-foreground block text-[10px] uppercase">Notes</span>
                    <p className="italic text-amber-900 bg-amber-50/50 p-2 rounded-lg border border-amber-100">{medicine.notes}</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </CardContent>
      )}
    </Card>
  );
};

export default function PrescriptionTab({
  appointmentId,
}: {
  appointmentId: string;
}) {
  const deleteMutation = useDeletePrescriptionItem(appointmentId);

  const { data, isLoading, error } =
    usePrescriptionByAppointmentId(appointmentId);
  const { data: conclusionData } = useConclusionByAppointmentId(appointmentId);
  const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);
  const [isConclusionDialogOpen, setIsConclusionDialogOpen] = useState(false);
  const [dialogTab, setDialogTab] = useState<"findings" | "medicines" | "reports">("findings");
  const [isTemplateDialogOpen, setIsTemplateDialogOpen] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<Medicine | null>(null);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-8 sm:py-12">
        <div className="animate-spin rounded-full h-6 w-6 sm:h-8 sm:w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-4 sm:p-6 text-center">
        <div className="flex items-center justify-center gap-2 text-red-500">
          <AlertCircle className="h-4 w-4 sm:h-5 sm:w-5" />
          <p className="text-xs sm:text-sm">Error loading prescription</p>
        </div>
      </div>
    );
  }

  const medicines = data?.data?.medicines || [];
  const draftHistory = (data?.data?.draft_history ?? []) as DraftHistoryItem[];
  const pdfUrl = data?.data?.pdf_url;
  const instructionsByDoctor = data?.data?.instructions_by_doctor;
  const instructionsParts = instructionsByDoctor ? instructionsByDoctor.split("Recommended Tests:") : [];
  const initialFindings = instructionsParts[0] ? instructionsParts[0].replace("Clinical Findings:", "").trim() : "";
  const initialRecommendedTests = instructionsParts[1] ? instructionsParts[1].trim() : "";
  const nextVisitDate = data?.data?.next_visit_date;
  const dictationAssistant = (data?.data?.dictation_assistant ??
    null) as DictationAssistantConfig | null;
  const doctorAddedCount = medicines.filter(
    (medicine: Medicine) => medicine.medicine_source === "doctor_added",
  ).length;
  const voiceAddedCount = medicines.filter(
    (medicine: Medicine) => medicine.created_via === "speech",
  ).length;
  const speechDraftHistoryCount = draftHistory.filter(
    (draft) => draft.source_type === "speech",
  ).length;

  // Conclusion data
  const conclusionFiles = (conclusionData?.data?.conclusion_report_files ??
    []) as ConclusionReportFile[];
  const conclusionType = conclusionFiles.map((file) => file.type);
  const fileUrl = conclusionFiles.map((file) => file.url);

  // Check if both prescription and conclusion are empty
  const hasPrescriptionData =
    medicines.length > 0 || instructionsByDoctor || nextVisitDate || pdfUrl;
  const hasConclusionData =
    conclusionType.length > 0 ||
    fileUrl.length > 0 ||
    instructionsByDoctor ||
    nextVisitDate;

  if (!hasPrescriptionData && !hasConclusionData) {
    return (
      <>
        <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2 mt-4 mb-4 w-full">
          <Button
            type="button"
            variant="outline"
            onClick={() => setIsTemplateDialogOpen(true)}
            className="w-full sm:w-auto h-8 sm:h-9 text-xs sm:text-sm"
          >
            <ClipboardList className="mr-1.5 h-3.5 w-3.5" />
            Use Template
          </Button>
          <Button
            type="button"
            onClick={() => {
              setDialogTab("medicines");
              setIsAddDialogOpen(true);
            }}
            className="w-full sm:w-auto h-8 sm:h-9 text-xs sm:text-sm"
          >
            Add Prescription
          </Button>
        </div>
        <Card>
          <CardContent className="p-6 sm:p-8 text-center">
            <div className="flex flex-col items-center gap-2 sm:gap-3">
              <FileText className="h-10 w-10 sm:h-12 sm:w-12 text-muted-foreground" />
              <p className="text-xs sm:text-sm text-muted-foreground">
                No prescription or conclusion available
              </p>
              <p className="text-xs sm:text-sm text-muted-foreground">
                Start by adding findings, diagnostics, or a prescription
              </p>
            </div>
          </CardContent>
        </Card>
        <AddPrescriptionDialog
          open={isAddDialogOpen}
          onOpenChange={setIsAddDialogOpen}
          initialTab={dialogTab}
          assistantConfig={dictationAssistant}
          initialMedicines={medicines}
          initialFindings={initialFindings}
          initialNextVisitDate={nextVisitDate}
          initialRecommendedTests={initialRecommendedTests}
          initialGeneralNotes={data?.data?.follow_up_note}
        />
        <AssignMedicineTemplateDialog
          appointmentId={appointmentId}
          open={isTemplateDialogOpen}
          onOpenChange={setIsTemplateDialogOpen}
        />
      </>
    );
  }

  return (
    <div className="space-y-4 sm:space-y-5 md:space-y-6">
      <div className="flex flex-wrap items-center justify-end gap-2 w-full">
        <Button
          type="button"
          variant="outline"
          onClick={() => setIsTemplateDialogOpen(true)}
          className="w-full sm:w-auto h-8 sm:h-9 text-xs sm:text-sm"
        >
          <ClipboardList className="mr-1.5 h-3.5 w-3.5" />
          Use Template
        </Button>
        <Button
          type="button"
          onClick={() => {
            setDialogTab("medicines");
            setIsAddDialogOpen(true);
          }}
          className="w-full sm:w-auto h-8 sm:h-9 text-xs sm:text-sm"
        >
          Add Prescription
        </Button>
      </div>

      {/* Medicines List - Accordion */}
      {medicines.length > 0 && (
        <div className="space-y-3 sm:space-y-4">
          <div className="flex items-center justify-between gap-3 w-full flex-wrap">
            <div className="flex items-center gap-2">
              <div className="p-1.5 sm:p-2 rounded-lg bg-primary/10">
                <Clock className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-primary" />
              </div>
              <div>
                <h3 className="font-semibold text-sm sm:text-base md:text-lg">
                  Prescribed Medicines
                </h3>
                <div className="flex flex-wrap gap-1.5 mt-0.5">
                  <Badge
                    variant="secondary"
                    className="text-[10px] sm:text-xs px-1.5 sm:px-2"
                  >
                    {medicines.length} Items
                  </Badge>
                  {voiceAddedCount > 0 && (
                    <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-100 text-[10px] sm:text-xs px-1.5 sm:px-2">
                      {voiceAddedCount} Voice-added
                    </Badge>
                  )}
                  {doctorAddedCount > 0 && (
                    <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100 text-[10px] sm:text-xs px-1.5 sm:px-2">
                      {doctorAddedCount} Doctor-added
                    </Badge>
                  )}
                </div>
              </div>
            </div>

            <Button
              type="button"
              variant="outline"
              onClick={() => setIsConclusionDialogOpen(true)}
              className="text-xs text-blue-600 border-blue-200 hover:bg-blue-50/50 rounded-xl flex items-center gap-1.5 shadow-sm"
            >
              <FileText className="h-3.5 w-3.5" />
              Add / Edit Diagnostics
            </Button>
          </div>

          <div className="space-y-2 sm:space-y-3">
            {medicines.map((medicine: Medicine, index: number) => (
              <MedicineAccordionItem
                key={medicine.prescription_id}
                medicine={medicine}
                index={index + 1}
                onDelete={(med) => setDeleteTarget(med)}
                onEdit={(med) => {
                  setDialogTab("medicines");
                  setIsAddDialogOpen(true);
                }}
              />
            ))}
          </div>

          {/* Recommended Diagnostics / Tests */}
          {((instructionsByDoctor && instructionsByDoctor.includes("Recommended Tests:")) || conclusionFiles.length > 0) && (
            <Card className="overflow-hidden border border-indigo-100 hover:shadow-md transition-all duration-300 rounded-2xl bg-linear-to-br from-indigo-50/10 to-indigo-50/30 mt-4">
              <CardHeader className="p-4 sm:p-5 border-b border-indigo-100/50 bg-indigo-50/20">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <FileText className="h-5 w-5 text-indigo-600" />
                    <CardTitle className="text-sm font-bold text-indigo-900">Recommended Diagnostics & Patient Reports</CardTitle>
                  </div>
                  <Badge className="bg-indigo-100 text-indigo-700 hover:bg-indigo-100 text-[10px] uppercase font-bold tracking-wider">
                    Diagnostics Active
                  </Badge>
                </div>
              </CardHeader>
              <CardContent className="p-4 sm:p-5 space-y-4">
                {instructionsByDoctor && instructionsByDoctor.includes("Recommended Tests:") && (
                  <div className="space-y-1.5">
                    <span className="text-[10px] uppercase text-muted-foreground font-bold tracking-wider">Assigned Tests</span>
                    <p className="text-xs sm:text-sm font-medium text-foreground bg-background p-3 rounded-xl border border-indigo-50 whitespace-pre-line leading-relaxed">
                      {instructionsByDoctor.split("Recommended Tests:")[1]?.trim()}
                    </p>
                  </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                  {/* Doctor Uploads */}
                  <div className="space-y-2">
                    <span className="text-[10px] uppercase text-muted-foreground font-bold tracking-wider block">Doctor Reference Files</span>
                    {conclusionFiles.length > 0 ? (
                      <div className="space-y-1.5">
                        {conclusionFiles.map((file, idx) => (
                          <a
                            key={file.id || idx}
                            href={file.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center justify-between p-2.5 bg-background hover:bg-indigo-50/30 border border-muted rounded-xl transition-all text-xs text-indigo-700 font-semibold group"
                          >
                            <span className="truncate flex-1 pr-2">{file.name || `Reference Document #${idx + 1}`}</span>
                            <ExternalLink className="h-3.5 w-3.5 opacity-60 group-hover:opacity-100" />
                          </a>
                        ))}
                      </div>
                    ) : (
                      <p className="text-xs italic text-muted-foreground bg-background/50 p-3 rounded-xl border border-dashed text-center">No reference files uploaded by doctor</p>
                    )}
                  </div>

                  {/* Patient Upload Status */}
                  <div className="space-y-2">
                    <span className="text-[10px] uppercase text-muted-foreground font-bold tracking-wider block">Patient Report Upload</span>
                    {conclusionFiles.some(f => f.type === "patient-uploaded" || f.name?.toLowerCase().includes("patient")) ? (
                      <div className="p-3 bg-emerald-50/50 border border-emerald-100 rounded-xl flex items-center gap-2">
                        <div className="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse" />
                        <span className="text-xs text-emerald-800 font-bold">Patient uploaded report files successfully</span>
                      </div>
                    ) : (
                      <div className="p-3 bg-amber-50/50 border border-amber-100 rounded-xl flex items-center justify-between gap-2">
                        <div className="flex items-center gap-2">
                          <div className="h-2 w-2 rounded-full bg-amber-400" />
                          <span className="text-xs text-amber-800 font-medium">Reports Pending Patient Upload</span>
                        </div>
                        <Badge variant="outline" className="text-[9px] bg-white border-amber-200 text-amber-700">Awaiting Upload</Badge>
                      </div>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>
          )}

          {/* PDF Download Button */}
          {pdfUrl && (
            <div className="pt-2 text-right">
              <a
                href={pdfUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-1.5 sm:py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-[11px] sm:text-sm font-medium w-full sm:w-auto"
              >
                <Download className="h-3 w-3 sm:h-4 sm:w-4" />
                Download Prescription (PDF)
                <ExternalLink className="h-2.5 w-2.5 sm:h-3 sm:w-3" />
              </a>
            </div>
          )}
        </div>
      )}

      {draftHistory.length > 0 && (
        <Card className="overflow-hidden p-0">
          <CardHeader className="pb-2 sm:pb-3 p-3 sm:p-4 border-b">
            <CardTitle className="flex flex-wrap items-center gap-2 text-sm sm:text-base">
              <Mic className="h-4 w-4 text-primary" />
              Draft Verification
              <Badge
                variant="secondary"
                className="text-[10px] sm:text-xs px-1.5 sm:px-2"
              >
                {draftHistory.length} Drafts
              </Badge>
              {speechDraftHistoryCount > 0 && (
                <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-100 text-[10px] sm:text-xs px-1.5 sm:px-2">
                  {speechDraftHistoryCount} Voice
                </Badge>
              )}
            </CardTitle>
          </CardHeader>

          <CardContent className="p-3 sm:p-4 space-y-3">
            {draftHistory.map((draft) => (
              <div
                key={draft.id}
                className="rounded-lg border bg-muted/20 p-3 sm:p-4 space-y-3"
              >
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <Badge
                      className={`text-[10px] sm:text-xs px-1.5 sm:px-2 ${draft.source_type === "speech"
                        ? "bg-blue-100 text-blue-700 hover:bg-blue-100"
                        : "bg-slate-100 text-slate-700 hover:bg-slate-100"
                        }`}
                    >
                      {draft.source_type === "speech"
                        ? "Voice Draft"
                        : "Typed Draft"}
                    </Badge>
                    {draft.medicine_source === "doctor_added" && (
                      <Badge className="bg-amber-100 text-amber-700 hover:bg-amber-100 text-[10px] sm:text-xs px-1.5 sm:px-2">
                        Doctor-added medicine
                      </Badge>
                    )}
                    {draft.medicine_source === "inventory" && (
                      <Badge
                        variant="outline"
                        className="text-[10px] sm:text-xs px-1.5 sm:px-2"
                      >
                        Stock medicine
                      </Badge>
                    )}
                    {typeof draft.confidence_score === "number" && (
                      <Badge
                        variant="outline"
                        className="text-[10px] sm:text-xs px-1.5 sm:px-2"
                      >
                        Confidence {draft.confidence_score}%
                      </Badge>
                    )}
                  </div>

                  {draft.applied_at && (
                    <p className="text-[10px] sm:text-xs text-muted-foreground">
                      Applied{" "}
                      {new Date(draft.applied_at).toLocaleString("en-US", {
                        year: "numeric",
                        month: "short",
                        day: "numeric",
                        hour: "numeric",
                        minute: "2-digit",
                      })}
                    </p>
                  )}
                </div>

                {draft.input_text && (
                  <div className="space-y-1">
                    <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                      Doctor Input
                    </p>
                    <p className="text-xs sm:text-sm leading-relaxed wrap-break-word">
                      {draft.input_text}
                    </p>
                  </div>
                )}

                {draft.created_medicines &&
                  draft.created_medicines.length > 0 && (
                    <div className="space-y-1">
                      <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                        Saved Medicines
                      </p>
                      <div className="flex flex-wrap gap-2">
                        {draft.created_medicines.map((medicine, index) => (
                          <Badge
                            key={`${draft.id}-medicine-${medicine.prescription_id ?? index}`}
                            variant="outline"
                            className="text-[10px] sm:text-xs px-1.5 sm:px-2"
                          >
                            {medicine.medicine_name}
                            {medicine.medicine_source === "doctor_added"
                              ? " • doctor-added"
                              : medicine.medicine_source === "inventory"
                                ? " • stock"
                                : ""}
                          </Badge>
                        ))}
                      </div>
                    </div>
                  )}

                {(draft.warnings?.length || draft.missing_fields?.length) && (
                  <div className="rounded-lg bg-amber-50 p-2.5 sm:p-3">
                    <p className="text-[10px] sm:text-xs text-amber-700 uppercase tracking-wide mb-1">
                      Review Notes
                    </p>
                    {draft.warnings?.length ? (
                      <ul className="list-disc list-inside space-y-1 text-[11px] sm:text-sm text-amber-800">
                        {draft.warnings.map((warning) => (
                          <li key={`${draft.id}-warning-${warning}`}>
                            {warning}
                          </li>
                        ))}
                      </ul>
                    ) : null}
                    {draft.missing_fields?.length ? (
                      <p className="text-[11px] sm:text-sm text-amber-800 mt-1">
                        Missing fields: {draft.missing_fields.join(", ")}
                      </p>
                    ) : null}
                  </div>
                )}
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      {/* Doctor Instructions & Next Visit Card */}
      {(instructionsByDoctor || nextVisitDate) && (
        <Card className="overflow-hidden p-0">
          <CardHeader className="pb-2 sm:pb-3 p-3 sm:p-4 border-b">
            <CardTitle className="flex items-center gap-2 text-sm sm:text-base">
              {/* Doctor's Advice */}
              Conclusion
            </CardTitle>
          </CardHeader>

          <CardContent className="p-0">
            {instructionsByDoctor && (
              <div className="flex items-start gap-2 sm:gap-3 p-2 sm:p-3 bg-muted/30 rounded-lg">
                <div className="p-1.5 sm:p-2 rounded-lg bg-primary/10 shrink-0">
                  <FileText className="h-3 w-3 sm:h-4 sm:w-4 text-primary" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-[9px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                    Instructions by Doctor
                  </p>
                  <p className="text-[11px] sm:text-sm mt-1 leading-relaxed wrap-break-word">
                    {instructionsByDoctor}
                  </p>
                </div>
              </div>
            )}

            {nextVisitDate && (
              <div className="flex items-start gap-2 sm:gap-3 p-2 sm:p-3 bg-muted/30 rounded-lg">
                <div className="p-1.5 sm:p-2 rounded-lg bg-green-100 shrink-0">
                  <Calendar className="h-3 w-3 sm:h-4 sm:w-4 text-green-600" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-[9px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                    Next Visit Date
                  </p>
                  <p className="text-[11px] sm:text-sm font-medium mt-1 wrap-break-word">
                    {new Date(nextVisitDate).toLocaleDateString("en-US", {
                      weekday: "long",
                      year: "numeric",
                      month: "long",
                      day: "numeric",
                    })}
                  </p>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* File URLs - Only show for type "other" */}
      {conclusionType.includes("other") && fileUrl.length > 0 && (
        <div className="mt-3 sm:mt-4 p-4 border rounded-lg">
          <p className="text-base font-medium tracking-wide mb-1 sm:mb-2">
            Uploaded by Doctor
          </p>
          <div className="space-y-2">
            {fileUrl.map((url: string, index: number) => (
              <a
                key={index}
                href={url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center justify-between p-2 bg-white rounded border hover:bg-blue-50 transition-colors"
              >
                <div className="flex items-center gap-2">
                  <FileText className="h-4 w-4 text-blue-600" />
                  <span className="text-sm font-medium text-blue-800 truncate flex-1">
                    File {index + 1}
                  </span>
                </div>
                <ExternalLink className="h-4 w-4 text-blue-600 shrink-0" />
              </a>
            ))}
          </div>
        </div>
      )}

      <AddPrescriptionDialog
        open={isAddDialogOpen}
        onOpenChange={setIsAddDialogOpen}
        initialTab={dialogTab}
        assistantConfig={dictationAssistant}
        initialMedicines={medicines}
        initialFindings={initialFindings}
        initialNextVisitDate={nextVisitDate}
        initialRecommendedTests={initialRecommendedTests}
        initialGeneralNotes={data?.data?.follow_up_note}
      />

      <AddConclusionDialog
        appointmentId={appointmentId}
        open={isConclusionDialogOpen}
        onOpenChange={setIsConclusionDialogOpen}
      />

      <AssignMedicineTemplateDialog
        appointmentId={appointmentId}
        open={isTemplateDialogOpen}
        onOpenChange={setIsTemplateDialogOpen}
      />

      {deleteTarget && (
        <Dialog
          open={!!deleteTarget}
          onOpenChange={(open) => !open && setDeleteTarget(null)}
        >
          <DialogContent className="sm:max-w-sm">
            <DialogHeader>
              <DialogTitle className="text-destructive flex items-center gap-2">
                Remove Medicine
              </DialogTitle>
              <DialogDescription>
                Are you sure you want to remove{" "}
                <strong className="text-foreground">{deleteTarget.name}</strong>{" "}
                from this prescription? This action cannot be undone.
              </DialogDescription>
            </DialogHeader>
            <DialogFooter className="flex sm:justify-end gap-2 pt-4">
              <Button
                type="button"
                variant="outline"
                onClick={() => setDeleteTarget(null)}
                disabled={deleteMutation.isPending}
              >
                Cancel
              </Button>
              <Button
                type="button"
                variant="destructive"
                disabled={deleteMutation.isPending}
                onClick={async () => {
                  try {
                    const remainingMedicines = medicines.filter(
                      (m) => m.prescription_id !== deleteTarget.prescription_id
                    );

                    const medicinesPayload = remainingMedicines.map((med) => {
                      const timings: string[] = [];
                      const timesStr = String(med.times || "").toLowerCase();
                      if (timesStr.includes("morning")) timings.push("morning");
                      if (timesStr.includes("afternoon")) timings.push("afternoon");
                      if (timesStr.includes("evening")) timings.push("evening");
                      if (timesStr.includes("night")) timings.push("night");

                      const mapFrequencyLabelToValue = (lbl: string): string => {
                        const norm = String(lbl || "").toLowerCase().trim();
                        if (norm.includes("once") || norm === "od") return "OD";
                        if (norm.includes("twice") || norm === "bd") return "BD";
                        if (norm.includes("three") || norm === "tds") return "TDS";
                        if (norm.includes("sos")) return "SOS";
                        return "OD";
                      };

                      const ensureValidDate = (dateStr: any): string | null => {
                        if (!dateStr) return null;
                        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
                        const parsed = Date.parse(dateStr);
                        if (!isNaN(parsed)) return new Date(parsed).toISOString().split("T")[0];
                        return null;
                      };

                      const rawDate = med.date || "";
                      let fallbackStartDate = new Date().toISOString().split("T")[0];
                      if (rawDate && /^\d{4}-\d{2}-\d{2}$/.test(rawDate.split(" - ")[0])) {
                        fallbackStartDate = rawDate.split(" - ")[0];
                      }

                      return {
                        medicine_id: med.prescription_id || med.medicine_id || null,
                        medicine_name: (med.name || "").trim(),
                        medication_type: med.type || "tablet",
                        strength: med.strength || "",
                        dosage: med.dosage,
                        frequency: med.frequency || mapFrequencyLabelToValue(med.frequencylabel) || "OD",
                        timings,
                        meal: med.meal || "after_meal",
                        application_area: med.application_area || "",
                        remarks: med.notes || "",
                        start_date: ensureValidDate(med.start_date) || fallbackStartDate,
                        end_date: ensureValidDate(med.end_date) || null,
                        instructions: med.instructions?.join(", ") || "",
                        follow_up_note: med.follow_up_note || "",
                      };
                    });

                    await deleteMutation.mutateAsync(deleteTarget.prescription_id);
                  } catch (err) {
                    console.error(err);
                  } finally {
                    setDeleteTarget(null);
                  }
                }}
              >
                {deleteMutation.isPending ? "Removing..." : "Remove"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      )}
    </div>
  );
}
