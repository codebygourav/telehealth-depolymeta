'use client';

import { Button } from '@/components/ui/button';
import { AlertCircle, Loader2 } from 'lucide-react';

interface PrescriptionLoadingStateProps {
    onBack: () => void;
}

export const PrescriptionLoadingState = () => (
    <div className="flex flex-col items-center justify-center min-h-[40vh] space-y-4">
        <Loader2 className="w-10 h-10 text-primary animate-spin" />
        <p className="font-medium text-on-surface-variant">Loading details...</p>
    </div>
);

export const PrescriptionErrorState = ({
    message,
    onBack,
}: {
    message?: string;
    onBack: () => void;
}) => (
    <div className="text-center py-20 bg-destructive/5 global-radius border border-dashed border-destructive/20 space-y-4">
        <AlertCircle className="w-12 h-12 mx-auto text-destructive" />
        <h3 className="text-xl font-bold text-destructive">Failed to load details</h3>
        <p className="max-w-md mx-auto text-on-surface-variant">
            {message || 'There was an error fetching medication details.'}
        </p>
        <Button onClick={onBack} variant="outline">
            Go Back
        </Button>
    </div>
);
