'use client';

import { MedicineItem } from '@/components/pages/my-medicines/MedicineItem';
import { MedicineDetail } from '@/types/prescriptions';
import { ExternalLink, Pill } from 'lucide-react';

interface PrescribedMedicinesSectionProps {
    medicines: MedicineDetail[];
    pdfUrl?: string;
    /** Show the PDF link inline next to the section title (used when DoctorInfoHeader is hidden) */
    showInlinePdfLink?: boolean;
    cardGrid?: string;
}

export const PrescribedMedicinesSection = ({
    medicines,
    pdfUrl,
    showInlinePdfLink = false,
    cardGrid = 'grid-cols-1 gap-6',
}: PrescribedMedicinesSectionProps) => {
    return (
        <div className="space-y-6">
            {/* Header row: title + optional inline PDF link */}
            <div className="grid grid-cols-1 gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h3 className="flex items-center gap-2 text-lg font-bold sm:text-xl g-text-dark font-headline">
                    <Pill className="w-5 h-5 sm:w-6 sm:h-6" />
                    Prescribed Medicines
                </h3>
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
