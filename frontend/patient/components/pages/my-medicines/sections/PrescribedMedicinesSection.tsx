'use client';

import { MedicineItem } from '@/components/pages/my-medicines/MedicineItem';
import { Button } from '@/components/ui/button';
import { MedicineDetail } from '@/types/prescriptions';
import { ArrowUpRight, Clock, Pill } from 'lucide-react';

interface PrescribedMedicinesSectionProps {
    medicines: MedicineDetail[];
    pdfUrl?: string;
    doctorName?: string;
    prescribedAt?: string;
    /** Show the PDF link inline next to the section title (used when DoctorInfoHeader is hidden) */
    showInlinePdfLink?: boolean;
    cardGrid?: string;
}

export const PrescribedMedicinesSection = ({
    medicines,
    pdfUrl,
    doctorName,
    prescribedAt,
    showInlinePdfLink = false,
    cardGrid = 'grid-cols-1 gap-6',
}: PrescribedMedicinesSectionProps) => {
    return (
        <div className="space-y-6 ">
            {/* Header row: title + optional inline PDF link */}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex items-start gap-2 text-lg font-bold sm:text-xl g-text-dark font-headline">
                <div className="p-3 sm:p-4 bg-light-gray text-primary global-radius shrink-0 h-full">
                    <Pill className="size-6" />
                </div>
                

                {(doctorName || prescribedAt) && (
                            <div>
                                <h2 className="text-lg font-bold sm:text-xl g-text-dark font-headline">Prescribed Medicines</h2>
                                {doctorName ? (
                                    <p className="g-text-sm g-text-muted font-medium">
                                        Prescribed by:{" "}
                                        <span className="font-s g-text-muted">{doctorName}</span>
                                    </p>
                                ) : null}
                                {prescribedAt ? (
                                    <p className="g-text-sm g-text-muted font-medium">
                                        Prescribed at:{" "}
                                        <span className="font-s g-text-muted">{prescribedAt}</span>
                                    </p>
                                ) : null}
                            </div>
                    )}
                </div>
               
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    {showInlinePdfLink && pdfUrl ? (
                        <Button asChild className="text-white btn-primary-cta w-full sm:w-auto m-0">
                            <a href={pdfUrl} target="_blank" rel="noopener noreferrer">
                                <span className="font-bold text-white">View PDF</span>
                                <ArrowUpRight className="w-5 h-5 m-0 text-white" />
                            </a>
                        </Button>
                    ) : null}
                </div>
            </div>

            {/* Medicines grid */}
            <div className={`grid ${cardGrid}`}>
                {medicines.map((med, idx) => (
                    <MedicineItem key={idx} medicine={med} />
                ))}
            </div>
        </div>
    );
};
