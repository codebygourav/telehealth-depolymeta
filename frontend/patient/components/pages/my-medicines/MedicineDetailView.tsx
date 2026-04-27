'use client';

import { DetailHeader } from '@/components/custom/DetailHeader';
import { MedicineActionPlan } from '@/components/pages/my-medicines/MedicineActionPlan';
import { MedicineItem } from '@/components/pages/my-medicines/MedicineItem';
import { Button } from '@/components/ui/button';
import { usePrescriptionDetail } from '@/queries/usePrescriptionDetail';
import {
    AlertCircle,
    Calendar as CalendarIcon,
    ExternalLink,
    FileText,
    Loader2,
    Pill,
    Stethoscope,
} from 'lucide-react';
import { motion } from 'motion/react';

interface MedicineDetailViewProps {
    prescriptionId: string;
    onBack: () => void;
    symptoms?: string;
    showTopHeader?: boolean;
    showDoctorHead?: boolean;
    cardGrid?: string;
    footerActionGrid?: string;
    doctorUserId?: string;
}

export const MedicineDetailView = ({
    prescriptionId,
    onBack,
    symptoms,
    showTopHeader = true,
    showDoctorHead = true,
    cardGrid = 'grid-cols-1 gap-6',
    footerActionGrid = 'grid-cols-1 gap-6',
    doctorUserId,
}: MedicineDetailViewProps) => {
    const {
        data: detailResponse,
        isLoading: isDetailLoading,
        isError: isDetailError,
        error: detailError,
    } = usePrescriptionDetail(prescriptionId);
    return (
        <div className="space-y-8 animate-in max-w-5xl  mx-auto fade-in slide-in-from-bottom-4 duration-500">
            {showTopHeader && (
                <DetailHeader
                    title="Medicine Details"
                    subtitle="Detailed information about your prescription."
                    onBack={onBack}
                />
            )}
            {isDetailLoading ? (
                <div className="flex flex-col items-center justify-center min-h-[40vh] space-y-4">
                    <Loader2 className="w-10 h-10 text-primary animate-spin" />
                    <p className="text-on-surface-variant font-medium">
                        Loading details...
                    </p>
                </div>
            ) : isDetailError || !detailResponse?.success ? (
                <div className="text-center py-20 bg-destructive/5 rounded-[2rem] border border-dashed border-destructive/20 space-y-4">
                    <AlertCircle className="w-12 h-12 text-destructive mx-auto" />
                    <h3 className="text-xl font-bold text-destructive">
                        Failed to load details
                    </h3>
                    <p className="text-on-surface-variant max-w-md mx-auto">
                        {detailError?.message ||
                            'There was an error fetching medication details.'}
                    </p>
                    <Button onClick={onBack} variant="outline">
                        Go Back
                    </Button>
                </div>
            ) : (
                <div className="space-y-8">
                    {detailResponse.data.medicines.length > 1 && (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            className="bg-white rounded-[2.5rem] p-5 sm:p-8 shadow-sm border border-outline-variant/10"
                        >
                            <div className="space-y-8">
                                {showDoctorHead && (
                                    <>
                                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-8 border-b border-outline-variant/10">
                                            <div className="flex items-center gap-4">
                                                <div className="p-3 sm:p-4 bg-emerald-50 text-emerald-600 rounded-2xl shrink-0">
                                                    <Stethoscope className="w-7 h-7 sm:w-8 h-8" />
                                                </div>
                                                <div>
                                                    <p className="text-[10px] uppercase tracking-widest font-bold text-on-surface-variant/60 mb-0.5">
                                                        Doctor
                                                    </p>
                                                    <p className="text-lg sm:text-xl font-bold text-primary leading-tight">
                                                        {
                                                            detailResponse.data
                                                                .doctor_name
                                                        }
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-4">
                                                <div className="p-3 sm:p-4 bg-surface-container-low rounded-2xl text-on-surface-variant/50 shrink-0">
                                                    <CalendarIcon className="w-7 h-7 sm:w-8 h-8" />
                                                </div>
                                                <div>
                                                    <p className="text-[10px] uppercase tracking-widest font-bold text-on-surface-variant/60 mb-0.5">
                                                        Prescribed at
                                                    </p>
                                                    <p className="text-lg sm:text-xl font-bold text-primary leading-tight">
                                                        {detailResponse.data
                                                            .medicines[0]
                                                            ?.date || 'N/A'}
                                                    </p>
                                                </div>
                                            </div>
                                            {detailResponse.data.pdf_url && (
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    className="rounded-2xl border-primary/20 h-12 sm:h-14 px-5 sm:px-6 gap-2 hover:bg-primary/5 w-full sm:w-auto"
                                                >
                                                    <a
                                                        href={
                                                            detailResponse.data
                                                                .pdf_url
                                                        }
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        <FileText className="w-5 h-5 text-primary" />
                                                        <span className="font-bold text-primary">
                                                            View PDF
                                                        </span>
                                                    </a>
                                                </Button>
                                            )}
                                        </div>
                                    </>
                                )}

                                <div className="space-y-6">
                                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                                                {/* LEFT - TITLE */}
                                                <h3 className="text-lg sm:text-xl font-bold text-primary font-headline flex items-center gap-2">
                                                    <Pill className="w-5 h-5 sm:w-6 sm:h-6" />
                                                    Prescribed Medicines
                                                </h3>

                                                {/* RIGHT - BUTTON */}
                                                {!showDoctorHead && detailResponse.data.pdf_url && (
                                                    <a
                                                        href={detailResponse.data.pdf_url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center justify-center gap-2 text-primary px-4 sm:px-5 py-2 sm:py-2.5 rounded-lg font-bold text-sm outline transition-all whitespace-nowrap w-full sm:w-auto"
                                                    >
                                                        <span className="flex items-center gap-2">
                                                            View PDF
                                                            <ExternalLink className="w-4 h-4 sm:w-5 sm:h-5" />
                                                        </span>
                                                    </a>
                                                )}

                                            </div>
                                    <div className={`grid ${cardGrid}`}>
                                        {detailResponse.data.medicines.map(
                                            (med, idx) => (
                                                <MedicineItem
                                                    key={idx}
                                                    medicine={med}
                                                />
                                            ),
                                        )}
                                    </div>
                                </div>
                            </div>
                        </motion.div>
                    )}
                    <MedicineActionPlan
                        conclusion={detailResponse.data.instructions_by_doctor}
                        nextVisitDate={detailResponse.data.next_visit_date}
                        doctor_id={doctorUserId || detailResponse.data.doctor_id}
                        footerActionGridClassName={footerActionGrid}
                    />
                </div>
            )}
        </div>
    );
};
