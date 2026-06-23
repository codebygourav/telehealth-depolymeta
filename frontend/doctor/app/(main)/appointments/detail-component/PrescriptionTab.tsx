"use client";

import { useState, useCallback } from "react";
import {
  ChevronDown,
  ChevronUp,
  FileText,
  Calendar,
  Clock,
  AlertCircle,
  Download,
  ExternalLink,
  ClipboardList,
  Trash2,
} from "lucide-react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { usePrescriptionByAppointmentId } from "@/queries/usePrescriptionByAppointmentId";
import { useConclusionByAppointmentId } from "@/queries/useConclusionByAppointmentId";
import { getStatusColor } from "@/src/utils/getStatusColor";
import AddPrescriptionDialog from "@/components/pages/appoitment/AddPrescriptionDialog";
import AddConclusionDialog from "@/components/pages/appoitment/AddConclusionDialog";
import AssignMedicineTemplateDialog from "@/components/pages/appoitment/AssignMedicineTemplateDialog";
import { useDeletePrescriptionItem } from "@/queries/useDeletePrescriptionItem";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogFooter,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";

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
}

type MealTiming = "before_meal" | "after_meal" | "with_meal" | string;
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
}: {
  medicine: Medicine;
  index: number;
  onDelete: (medicine: Medicine) => void;
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
    <Card className="overflow-hidden p-0">
      <div
        className="cursor-pointer hover:bg-muted/30 transition-colors"
        onClick={toggleOpen}
        role="button"
        aria-expanded={isOpen}
        aria-controls={`medicine-details-${medicine.prescription_id}`}
      >
        <CardHeader className="p-3 sm:p-4">
          <div className="flex items-center justify-between gap-2">
            <div className="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
              <div className="p-1.5 sm:p-2 rounded-lg bg-primary/10 shrink-0">
                <span className="text-xs sm:text-sm font-semibold text-primary">
                  #{index}
                </span>
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                  <h4 className="font-semibold text-sm sm:text-base truncate">
                    {medicine.name}
                  </h4>
                  <Badge
                    variant="outline"
                    className="text-[9px] sm:text-xs px-1 sm:px-1.5"
                  >
                    {medicine.type}
                  </Badge>
                  <Badge
                    className={`${getStatusColor("session", medicine.status)} text-[9px] sm:text-xs px-1 sm:px-1.5`}
                  >
                    {medicine.status}
                  </Badge>
                </div>
                <div className="flex flex-wrap items-center gap-1 sm:gap-2 mt-1 text-[10px] sm:text-sm text-muted-foreground">
                  <span>{medicine.dosage}</span>
                  <span className="hidden xs:inline">•</span>
                  <span className="capitalize">
                    {medicine.use_type === 'sos'
                      ? 'SOS (As Needed)'
                      : (medicine.use_type && medicine.use_type !== 'regular'
                          ? medicine.use_type.replace('_', ' ')
                          : medicine.frequencylabel)}
                  </span>
                  {medicine.use_type !== 'sos' && medicine.times && (
                    <>
                      <span className="hidden xs:inline">•</span>
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
                className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10 shrink-0"
                onClick={(e) => {
                  e.stopPropagation();
                  onDelete(medicine);
                }}
              >
                <Trash2 className="h-4 w-4" />
              </Button>
              {isOpen ? (
                <ChevronUp className="h-4 w-4 sm:h-5 sm:w-5 text-muted-foreground shrink-0" />
              ) : (
                <ChevronDown className="h-4 w-4 sm:h-5 sm:w-5 text-muted-foreground shrink-0" />
              )}
            </div>
          </div>
        </CardHeader>
      </div>

      {isOpen && (
        <CardContent
          className="p-3 sm:p-4 pt-0 border-t"
          id={`medicine-details-${medicine.prescription_id}`}
        >
          <div className="grid grid-cols-1 xs:grid-cols-2 gap-3 sm:gap-4 mt-3 sm:mt-4">
            <div className="space-y-1 sm:space-y-2">
              <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                With Meal
              </p>
              <p className="text-xs sm:text-sm font-medium">
                {getMealLabel(medicine.meal)}
              </p>
            </div>

            <div className="space-y-1 sm:space-y-2">
              <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">
                Duration
              </p>
              <p className="text-xs sm:text-sm font-medium">{medicine.date}</p>
            </div>
          </div>

          {medicine.use_type === 'sos' && (
            <div className="grid grid-cols-1 xs:grid-cols-3 gap-3 sm:gap-4 mt-3 pt-3 border-t">
              {medicine.take_when && (
                <div className="space-y-1">
                  <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">Take When / Reason</p>
                  <p className="text-xs sm:text-sm font-medium capitalize">{medicine.take_when}</p>
                </div>
              )}
              {medicine.min_gap && (
                <div className="space-y-1">
                  <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">Minimum Gap</p>
                  <p className="text-xs sm:text-sm font-medium capitalize">{medicine.min_gap}</p>
                </div>
              )}
              {medicine.max_doses_per_day && (
                <div className="space-y-1">
                  <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide">Max Doses Per Day</p>
                  <p className="text-xs sm:text-sm font-medium capitalize">{medicine.max_doses_per_day}</p>
                </div>
              )}
            </div>
          )}

          {medicine.use_type && medicine.use_type !== 'regular' && medicine.use_type !== 'sos' && (
            <div className="space-y-1 mt-3 pt-3 border-t">
              <p className="text-[10px] sm:text-xs text-muted-foreground uppercase tracking-wide font-semibold">Special Instructions</p>
              <p className="text-xs sm:text-sm font-medium capitalize">Take as {medicine.use_type.replace('_', ' ')}</p>
            </div>
          )}

          {medicine.instructions && medicine.instructions.length > 0 && (
            <div className="mt-3 sm:mt-4 p-2 sm:p-3 bg-blue-50 rounded-lg">
              <p className="text-[10px] sm:text-xs text-blue-700 uppercase tracking-wide mb-1 sm:mb-2">
                Instructions
              </p>
              <ul className="list-disc list-inside space-y-0.5 sm:space-y-1">
                {medicine.instructions.map((ins: string, i: number) => (
                  <li
                    key={`${medicine.prescription_id}-instruction-${i}`}
                    className="text-[11px] sm:text-sm text-blue-800 wrap-break-word"
                  >
                    {ins}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {medicine.notes && (
            <div className="mt-3 p-2 sm:p-3 bg-yellow-50 rounded-lg">
              <p className="text-[10px] sm:text-xs text-yellow-700 uppercase tracking-wide mb-1">
                Notes
              </p>
              <p className="text-[11px] sm:text-sm text-yellow-800 wrap-break-word">
                {medicine.notes}
              </p>
            </div>
          )}
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
  const [isAddConclusionOpen, setIsAddConclusionOpen] = useState(false);
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
  const pdfUrl = data?.data?.pdf_url;
  const instructionsByDoctor = data?.data?.instructions_by_doctor;
  const nextVisitDate = data?.data?.next_visit_date;

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
        <div className="flex justify-end gap-2 mt-4 mb-4">
          <Button
            variant="outline"
            onClick={() => setIsAddConclusionOpen(true)}
            className="h-8 sm:h-9 text-xs sm:text-sm"
          >
            Add Conclusion
          </Button>
          <Button
            variant="outline"
            onClick={() => setIsTemplateDialogOpen(true)}
            className="h-8 sm:h-9 text-xs sm:text-sm"
          >
            <ClipboardList className="mr-1.5 h-3.5 w-3.5" />
            Use Template
          </Button>
          <Button
            onClick={() => setIsAddDialogOpen(true)}
            className="h-8 sm:h-9 text-xs sm:text-sm"
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
                Start by adding a prescription or conclusion
              </p>
            </div>
          </CardContent>
        </Card>
        <AddPrescriptionDialog
          open={isAddDialogOpen}
          onOpenChange={setIsAddDialogOpen}
        />
        <AssignMedicineTemplateDialog
          appointmentId={appointmentId}
          open={isTemplateDialogOpen}
          onOpenChange={setIsTemplateDialogOpen}
        />
        <AddConclusionDialog
          appointmentId={appointmentId}
          open={isAddConclusionOpen}
          onOpenChange={setIsAddConclusionOpen}
        />
      </>
    );
  }

  return (
    <div className="space-y-4 sm:space-y-5 md:space-y-6">
      <div className="flex justify-end gap-2">
        <Button
          variant="outline"
          onClick={() => setIsAddConclusionOpen(true)}
          className="w-full bg-muted sm:w-auto h-8 sm:h-9 text-xs sm:text-sm"
        >
          Add Conclusion
        </Button>
        <Button
          variant="outline"
          onClick={() => setIsTemplateDialogOpen(true)}
          className="w-full sm:w-auto h-8 sm:h-9 text-xs sm:text-sm"
        >
          <ClipboardList className="mr-1.5 h-3.5 w-3.5" />
          Use Template
        </Button>
        <Button
          onClick={() => setIsAddDialogOpen(true)}
          className="w-full sm:w-auto h-8 sm:h-9 text-xs sm:text-sm"
        >
          Add Prescription
        </Button>
      </div>
      {/* Medicines List - Accordion */}
      {medicines.length > 0 && (
        <div className="space-y-3 sm:space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div className="flex items-center gap-2">
              <div className="p-1.5 sm:p-2 rounded-lg bg-primary/10">
                <Clock className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-primary" />
              </div>
              <div>
                <h3 className="font-semibold text-sm sm:text-base md:text-lg">
                  Prescribed Medicines
                </h3>
                <Badge
                  variant="secondary"
                  className="text-[10px] sm:text-xs px-1.5 sm:px-2"
                >
                  {medicines.length} Items
                </Badge>
              </div>
              <div></div>
            </div>
          </div>

          <div className="space-y-2 sm:space-y-3">
            {medicines.map((medicine: Medicine, index: number) => (
              <MedicineAccordionItem
                key={medicine.prescription_id}
                medicine={medicine}
                index={index + 1}
                onDelete={(med) => setDeleteTarget(med)}
              />
            ))}
          </div>
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
                  <p className="text-[11px] sm:text-sm mt-1 leading-relaxed break-words">
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
                  <p className="text-[11px] sm:text-sm font-medium mt-1 break-words">
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
      />

      <AssignMedicineTemplateDialog
        appointmentId={appointmentId}
        open={isTemplateDialogOpen}
        onOpenChange={setIsTemplateDialogOpen}
      />

      <AddConclusionDialog
        appointmentId={appointmentId}
        open={isAddConclusionOpen}
        onOpenChange={setIsAddConclusionOpen}
      />

      {deleteTarget && (
        <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
          <DialogContent className="sm:max-w-sm">
            <DialogHeader>
              <DialogTitle className="text-destructive flex items-center gap-2">
                Remove Medicine
              </DialogTitle>
              <DialogDescription>
                Are you sure you want to remove <strong className="text-foreground">{deleteTarget.name}</strong> from this prescription? This action cannot be undone.
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
