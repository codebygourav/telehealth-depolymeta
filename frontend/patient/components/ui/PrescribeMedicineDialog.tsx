"use client";

import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { Pill, Loader2, XCircle } from "lucide-react";
import { MedicineCard } from "@/components/pages/my-medicines/MedicineCard";
import { useAppointmentDetail } from "@/queries/useAppointmentSummary";
import { usePrescriptions } from "@/queries/usePrescriptions";
import { ScrollArea } from "@/components/ui/scroll-area";

interface PrescribeMedicineDialogProps {
    isOpen: boolean;
    onClose: () => void;
    appointmentId: string | null;
}

export const PrescribeMedicineDialog = ({
    isOpen,
    onClose,
    appointmentId,
}: PrescribeMedicineDialogProps) => {
    // 1. Fetch Appointment Details to get the Patient ID
    const {
        data: appointmentResponse,
        isLoading: isAppointmentLoading,
        isError: isAppointmentError,
    } = useAppointmentDetail(appointmentId || "");

    const patientId = appointmentResponse?.data?.patient?.id;

    // 2. Fetch Active Prescriptions for the Patient
    const {
        data: prescriptionsResponse,
        isLoading: isPrescriptionsLoading,
        isError: isPrescriptionsError,
    } = usePrescriptions({
        patientID: patientId,
        filter: "current",
    });

    const prescriptions = prescriptionsResponse?.data || [];
    const isLoading = isAppointmentLoading || isPrescriptionsLoading;
    const isError = isAppointmentError || isPrescriptionsError;

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl sm:max-w-2xl p-0 gap-0 rounded-[2rem] overflow-hidden border-none shadow-2xl">
                <DialogHeader className="p-8 bg-[#013220] text-white">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                            <Pill className="w-6 h-6 text-white" />
                        </div>
                        <DialogTitle className="text-2xl font-bold font-headline">
                            Active Medications
                        </DialogTitle>
                    </div>
                    <DialogDescription className="text-white/70 text-sm">
                        Current prescriptions for the patient as of today.
                    </DialogDescription>
                </DialogHeader>

                <ScrollArea className="max-h-[60vh] p-6 lg:p-8 bg-surface">
                    {isLoading ? (
                        <div className="flex flex-col items-center justify-center py-20 gap-4">
                            <Loader2 className="w-10 h-10 animate-spin text-primary/40" />
                            <p className="text-muted-foreground font-medium">Loading medications...</p>
                        </div>
                    ) : isError ? (
                        <div className="flex flex-col items-center justify-center py-20 text-center gap-4">
                            <XCircle className="w-12 h-12 text-destructive/40" />
                            <div>
                                <h4 className="font-bold text-lg text-primary">Failed to load data</h4>
                                <p className="text-muted-foreground text-sm max-w-[250px]">
                                    There was an error fetching the patient's medications.
                                </p>
                            </div>
                        </div>
                    ) : prescriptions.length > 0 ? (
                        <div className="grid grid-cols-1 gap-4">
                            {prescriptions.map((prescription: any) => (
                                <MedicineCard
                                    key={prescription.appointment_id}
                                    prescription={prescription}
                                    status="current"
                                    onViewDetail={(id) => {
                                        // Optional: Handle detail view within consultation or external link
                                        window.open(`/my-medicines/${id}`, "_blank");
                                    }}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center py-20 text-center gap-4">
                            <div className="w-16 h-16 bg-muted/30 rounded-full flex items-center justify-center text-muted-foreground/30">
                                <Pill className="w-8 h-8" />
                            </div>
                            <div>
                                <h4 className="font-bold text-lg text-primary">No active medications</h4>
                                <p className="text-muted-foreground text-sm">
                                    This patient currently has no active prescriptions.
                                </p>
                            </div>
                        </div>
                    )}
                </ScrollArea>

                <div className="p-6 bg-surface border-t border-outline-variant/10 flex justify-end">
                    <button
                        onClick={onClose}
                        className="px-8 py-3 bg-[#013220] text-white rounded-full font-bold text-sm hover:opacity-90 transition-all shadow-md"
                    >
                        Close
                    </button>
                </div>
            </DialogContent>
        </Dialog>
    );
};
