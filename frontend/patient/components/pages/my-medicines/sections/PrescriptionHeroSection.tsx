'use client';

import HeroSection from '@/components/hero-section';
import { Button } from '@/components/ui/button';
import { ChevronLeft } from 'lucide-react';

interface PrescriptionHeroSectionProps {
    onBack: () => void;
}

export const PrescriptionHeroSection = ({
    onBack,
}: PrescriptionHeroSectionProps) => {
    return (
        <>
            <HeroSection
                title="Medicine Details"
                description="Detailed information about your prescription."
            />
            <Button onClick={onBack} className="bg-light-gray g-border rounded-full text-primary h-10 w-10">
                <ChevronLeft className="size-6" />
            </Button>
        </>
    );
};
