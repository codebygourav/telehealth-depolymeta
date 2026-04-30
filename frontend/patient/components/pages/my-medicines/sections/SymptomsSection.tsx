'use client';

import { motion } from 'motion/react';
import { Stethoscope } from 'lucide-react';

interface SymptomsSectionProps {
    symptoms: string;
}

export const SymptomsSection = ({ symptoms }: SymptomsSectionProps) => {
    if (!symptoms) return null;

    return (
        <motion.div
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="bg-white rounded-[2.5rem] p-5 sm:p-8 shadow-sm border border-outline-variant/10 space-y-4"
        >
            <div className="flex items-center gap-3 text-primary mb-2">
                <Stethoscope className="w-6 h-6" />
                <h3 className="text-xl font-bold font-headline">Symptoms Reported</h3>
            </div>
            <p className="text-on-surface-variant leading-relaxed font-medium italic">
                "{symptoms}"
            </p>
        </motion.div>
    );
};
