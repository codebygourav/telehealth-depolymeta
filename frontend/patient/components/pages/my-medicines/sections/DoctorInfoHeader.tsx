'use client';

import { Button } from '@/components/ui/button';
import { Calendar as CalendarIcon, ArrowUpRight, Clock } from 'lucide-react';
import { Separator } from "@/components/ui/separator"

interface DoctorInfoHeaderProps {
    doctorName: string;
    prescribedAt: string;
    pdfUrl?: string;
}

export const DoctorInfoHeader = ({
    doctorName,
    prescribedAt,
    pdfUrl,
}: DoctorInfoHeaderProps) => {
    return (
        <div className="flex flex-col justify-between gap-6 sm:flex-row sm:items-center ">
           
            {/* Doctor */}
            <div className="flex items-center gap-4">
                <div className="p-3 sm:p-4 bg-light-gray text-primary global-radius shrink-0">
                    <Clock className="w-7 h-7 sm:w-8 sm:h-8" />
                </div>
                <div>
                    <p className="g-text-md g-text-muted">
                      Prescribed by: <span className="font-bold g-text-muted">{doctorName}</span>
                    </p>
                    <p className="g-text-md g-text-muted"> Prescribed at: {prescribedAt}</p>
                </div>
            </div>
            
            {pdfUrl && (
                <Button
                    asChild
                    className="text-white btn-primary-cta"
                >
                    <a href={pdfUrl} target="_blank" rel="noopener noreferrer">
                        <span className="font-bold text-white">View PDF</span>
                        <ArrowUpRight className="w-5 h-5 m-0 text-white" />
                    </a>
                </Button>
            )}
        </div>
    );
};
