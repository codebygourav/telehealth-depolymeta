'use client';

import { FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PdfButtonSectionProps {
    pdfUrl: string;
}

export const PdfButtonSection = ({ pdfUrl }: PdfButtonSectionProps) => {
    return (
        <Button
            asChild
            variant="outline"
            className="w-full sm:w-auto h-12 gap-2 px-5 rounded-2xl border-primary/20 sm:h-14 sm:px-6 hover:bg-primary/5"
        >
            <a href={pdfUrl} target="_blank" rel="noopener noreferrer">
                <FileText className="w-5 h-5 text-primary" />
                <span className="font-bold text-primary">View PDF</span>
            </a>
        </Button>
    );
};
