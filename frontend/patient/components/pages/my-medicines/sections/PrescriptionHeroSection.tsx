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
            <Button onClick={onBack} className="global-radius btn-primary-cta">
                <ChevronLeft className="w-6 h-6 m-0" />
                Back
            </Button>
        </>
    );
};
