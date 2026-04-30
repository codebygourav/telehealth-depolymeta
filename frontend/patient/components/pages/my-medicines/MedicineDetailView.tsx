'use client';

import { MedicineActionPlan } from '@/components/pages/my-medicines/MedicineActionPlan';
import { DoctorInfoHeader } from '@/components/pages/my-medicines/sections/DoctorInfoHeader';
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
    footerActionGrid = 'grid-cols-1 gap-6',
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

                    {/* ── Prescription card (Doctor header + Medicines) */}
                    {(showDoctorHeader || showPrescribedMedicines) &&
                        detailResponse.data.medicines.length > 0 && (
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                className="p-5 bg-white border shadow-sm global-radius sm:p-8 border-outline-variant/10"
                            >
                                <div className="space-y-8">

                                    {/* Doctor info row */}
                                    {showDoctorHeader && (
                                        <DoctorInfoHeader
                                            doctorName={detailResponse.data.doctor_name}
                                            prescribedAt={
                                                detailResponse.data.medicines[0]?.date
                                            }
                                            pdfUrl={detailResponse.data.pdf_url}
                                        />
                                    )}

                                    {/* Prescribed medicines grid */}
                                    {showPrescribedMedicines && (
                                        <PrescribedMedicinesSection
                                            medicines={detailResponse.data.medicines}
                                            pdfUrl={detailResponse.data.pdf_url}
                                            showInlinePdfLink={!showDoctorHeader}
                                            cardGrid={cardGrid}
                                        />
                                    )}
                                </div>
                            </motion.div>
                        )}

                    {/* ── Conclusion + Next Visit ──────────────────── */}
                    {showActionPlan && (
                        <MedicineActionPlan
                            conclusion={detailResponse.data.instructions_by_doctor}
                            nextVisitDate={detailResponse.data.next_visit_date}
                            doctor_id={
                                doctorUserId || detailResponse.data.doctor_id
                            }
                            footerActionGridClassName={footerActionGrid}
                        />
                    )}
                </div>
            )}
        </div>
    );
};
