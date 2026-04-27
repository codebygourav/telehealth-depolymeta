'use client';

import { Button } from '@/components/ui/button';
import { MedicineDetailsData } from '@/types/prescriptions';
import {
    Calendar,
    CheckCircle2,
    FileText,
    Pill,
    Stethoscope,
} from 'lucide-react';
import { motion } from 'motion/react';
import { useRouter } from 'next/navigation';
import { MedicineItem } from './MedicineItem';

export const MedicineDetailsCard = ({
    data,
    doctorName,
}: {
    data: MedicineDetailsData;
    doctorName?: string;
}) => {
    const router = useRouter();
    const firstMed = data.medicines[0];

    return (
        <div className="max-w-4xl mx-auto space-y-8">
            {/* Main Info Card */}
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="bg-white rounded-[2.5rem] p-8 shadow-sm border border-outline-variant/10"
            >
                <div className="space-y-8">
                    <div className="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-outline-variant/10">
                        <div className="flex items-center gap-4">
                            <div className="p-4 bg-emerald-50 text-emerald-600 rounded-2xl">
                                <Stethoscope className="w-8 h-8" />
                            </div>
                            <div>
                                <p className="text-[10px] uppercase tracking-widest font-bold text-on-surface-variant/60 mb-0.5">
                                    Doctor
                                </p>
                                <p className="text-xl font-bold text-primary">
                                    {doctorName || 'Your Doctor'}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <div className="p-4 bg-surface-container-low rounded-2xl text-on-surface-variant/50">
                                <Calendar className="w-8 h-8" />
                            </div>
                            <div>
                                <p className="text-[10px] uppercase tracking-widest font-bold text-on-surface-variant/60 mb-0.5">
                                    Duration
                                </p>
                                <p className="text-xl font-bold text-primary">
                                    {firstMed?.date || 'N/A'}
                                </p>
                            </div>
                        </div>

                        {data.pdf_url && (
                            <Button
                                asChild
                                variant="outline"
                                className="rounded-2xl border-primary/20 h-14 px-6 gap-2 hover:bg-primary/5"
                            >
                                <a
                                    href={data.pdf_url}
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

                    <div className="space-y-6">
                        <h3 className="text-xl font-bold text-primary font-headline flex items-center gap-2">
                            <Pill className="w-6 h-6" />
                            Prescribed Medicines ds
                        </h3>
                        <div className="grid grid-cols-1 gap-4">
                            {data.medicines.map((med, idx) => (
                                <MedicineItem key={idx} medicine={med} />
                            ))}
                        </div>
                    </div>
                </div>
            </motion.div>

            {/* Doctor's Notes */}
            <div className="grid grid-cols-1 gap-6">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.3 }}
                    className="bg-white rounded-[2rem] p-8 shadow-sm border border-outline-variant/10 space-y-4"
                >
                    <div className="flex items-center gap-3 text-primary mb-2">
                        <CheckCircle2 className="w-6 h-6" />
                        <h3 className="text-xl font-bold font-headline">
                            Doctor's Instructions
                        </h3>
                    </div>
                    <p className="text-on-surface-variant leading-relaxed font-medium">
                        {data.instructions_by_doctor ||
                            'No special instructions provided.'}
                    </p>
                </motion.div>

                {/* Action/Next Visit Card */}
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.4 }}
                    className="bg-[#052116] text-white rounded-[2rem] p-8 shadow-xl flex flex-col md:flex-row items-center justify-between gap-6"
                >
                    <div className="flex items-center gap-6">
                        <div className="p-4 bg-white/10 rounded-2xl">
                            <Calendar className="w-8 h-8" />
                        </div>
                        <div>
                            <h3 className="text-2xl font-bold font-headline mb-1">
                                Next Visit
                            </h3>
                            <p className="text-white/70 font-medium">
                                {data.next_visit_date
                                    ? `Scheduled for ${data.next_visit_date}`
                                    : 'Not Scheduled Yet'}
                            </p>
                        </div>
                    </div>
                    <Button
                        className="w-full md:w-auto px-8 py-7 bg-white text-[#052116] font-bold rounded-2xl shadow-lg hover:bg-opacity-90 transition-all text-lg"
                        onClick={() => router.push('/appointments/book')}
                    >
                        Reschedule Visit
                    </Button>
                </motion.div>
            </div>
        </div>
    );
};
