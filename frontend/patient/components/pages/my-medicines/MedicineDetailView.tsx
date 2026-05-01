'use client';

import { MedicineActionPlan } from '@/components/pages/my-medicines/MedicineActionPlan';
import { PdfButtonSection } from '@/components/pages/my-medicines/sections/PdfButtonSection';
import { PrescribedMedicinesSection } from '@/components/pages/my-medicines/sections/PrescribedMedicinesSection';
import { PrescriptionHeroSection } from '@/components/pages/my-medicines/sections/PrescriptionHeroSection';
import {
    PrescriptionErrorState,
    PrescriptionLoadingState,
} from '@/components/pages/my-medicines/sections/PrescriptionLoadingState';
import { SymptomsSection } from '@/components/pages/my-medicines/sections/SymptomsSection';
import { usePrescriptionDetail } from '@/queries/usePrescriptionDetail';
import { motion } from 'motion/react';

interface MedicineDetailViewProps {
    prescriptionId: string;
    onBack?: () => void;
    showHeroSection?: boolean;
    showDoctorHeader?: boolean;
    showSymptoms?: boolean;
    showPdfButton?: boolean;
    showPrescribedMedicines?: boolean;
    showActionPlan?: boolean;
    symptoms?: string;
    doctorUserId?: string;
    cardGrid?: string;
    footerActionGrid?: string;
    appointmentDetailLayout?: boolean;
}

export const MedicineDetailView = ({
    prescriptionId,
    onBack = () => {},

    // Section flags — Medicine Detail page shows everything by default
    showHeroSection = true,
    showDoctorHeader = true,
    showSymptoms = false,
    showPdfButton = false,
    showPrescribedMedicines = true,
    showActionPlan = true,

    symptoms,
    doctorUserId,
    cardGrid = 'grid-cols-3 gap-6',
    footerActionGrid = 'grid-cols-1 gap-3.5',
    appointmentDetailLayout = false,
}: MedicineDetailViewProps) => {
    const {
        data: detailResponse,
        isLoading,
        isError,
        error,
    } = usePrescriptionDetail(prescriptionId);

    return (
        <div className="mx-auto space-y-8 duration-500 animate-in container-max-width fade-in slide-in-from-bottom-4">

            {/* ── Hero + Back button ─────────────────────────────── */}
            {showHeroSection && <PrescriptionHeroSection onBack={onBack} />}

            {/* ── Loading / Error states ─────────────────────────── */}
            {isLoading ? (
                <PrescriptionLoadingState />
            ) : isError || !detailResponse?.success ? (
                <PrescriptionErrorState
                    message={error?.message}
                    onBack={onBack}
                />
            ) : (
                <div className="space-y-8">

                    {/* ── Symptoms (Appointment Detail page) ──────── */}
                    {showSymptoms && symptoms && (
                        <SymptomsSection symptoms={symptoms} />
                    )}

                    {/* ── Standalone PDF button ────────────────────── */}
                    {showPdfButton && detailResponse.data.pdf_url && (
                        <PdfButtonSection pdfUrl={detailResponse.data.pdf_url} />
                    )}

                    {appointmentDetailLayout ? (
                        <div className="grid grid-cols-1 gap-1.5 md:grid-cols-6">
                            {/* ── Prescription card (Medicines) */}
                            {(showDoctorHeader || showPrescribedMedicines) &&
                                detailResponse.data.medicines.length > 0 && (
                                    <motion.div
                                        initial={{ opacity: 0, y: 20 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        className="p-4 bg-white border-light-gray g-border global-radius sm:p-5 md:col-span-6"
                                    >
                                        <div className="space-y-8">
                                            {showPrescribedMedicines && (
                                                <PrescribedMedicinesSection
                                                    doctorName={detailResponse.data.doctor_name}
                                                    showInlinePdfLink={true}
                                                    prescribedAt={detailResponse.data.medicines[0]?.date}
                                                    medicines={detailResponse.data.medicines}
                                                    pdfUrl={detailResponse.data.pdf_url}
                                                    cardGrid={cardGrid}
                                                />
                                            )}
                                        </div>
                                    </motion.div>
                                )}

                            {/* ── Next Visit only (hide Conclusion) */}
                            {showActionPlan ? (
                                <div className="md:col-span-4 md:col-start-9">
                                    <MedicineActionPlan
                                        conclusion={detailResponse.data.instructions_by_doctor ?? ""}
                                        nextVisitDate={detailResponse.data.next_visit_date ?? ""}
                                        doctor_id={doctorUserId || detailResponse.data.doctor_id || ""}
                                        footerActionGridClassName="grid-cols-1 gap-6"
                                        showConclusion={false}
                                    />
                                </div>
                            ) : null}
                        </div>
                    ) : (
                        <>
                            {/* ── Prescription card (Doctor header + Medicines) */}
                            {(showDoctorHeader || showPrescribedMedicines) &&
                                detailResponse.data.medicines.length > 0 && (
                                    <motion.div
                                        initial={{ opacity: 0, y: 20 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        className="p-4 bg-white border-light-gray shadow-sm global-radius sm:p-5"
                                    >
                                        <div className="space-y-8">
                                            {/* Prescribed medicines grid */}
                                            {showPrescribedMedicines && (
                                                <PrescribedMedicinesSection
                                                    doctorName={detailResponse.data.doctor_name}
                                                    showInlinePdfLink={true}
                                                    prescribedAt={detailResponse.data.medicines[0]?.date}
                                                    medicines={detailResponse.data.medicines}
                                                    pdfUrl={detailResponse.data.pdf_url}
                                                    cardGrid={cardGrid}
                                                />
                                            )}
                                        </div>
                                    </motion.div>
                                )}

                            {/* ── Conclusion + Next Visit ──────────────────── */}
                            {showActionPlan && (
                                <MedicineActionPlan
                                    conclusion={detailResponse.data.instructions_by_doctor ?? ""}
                                    nextVisitDate={detailResponse.data.next_visit_date ?? ""}
                                    doctor_id={doctorUserId || detailResponse.data.doctor_id || ""}
                                    footerActionGridClassName={footerActionGrid}
                                />
                            )}
                        </>
                    )}
                </div>
            )}
        </div>
    );
};
