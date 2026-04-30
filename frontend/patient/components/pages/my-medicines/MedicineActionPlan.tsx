'use client';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ArrowRight, Calendar, CheckCircle2, LucideIcon } from 'lucide-react';
import { motion } from 'motion/react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/userContext';

interface ActionPlanProps {
    conclusion: string;
    nextVisitDate: string;
    doctor_id: string;
    conclusionIcon?: LucideIcon;
    nextVisitIcon?: LucideIcon;
    buttonBgColor?: string;
    buttonTextColor?: string;
    buttonText?: string;
    footerActionGridClassName?: string;
}

export const MedicineActionPlan = ({
    conclusion,
    nextVisitDate,
    doctor_id,
    conclusionIcon: ConclusionIcon = CheckCircle2,
    nextVisitIcon: NextVisitIcon = Calendar,
    buttonBgColor = 'bg-white',
    buttonTextColor = 'text-primary',
    buttonText = 'Reschedule Visit',
    footerActionGridClassName = 'grid-cols-1 gap-6',
}: ActionPlanProps) => {
    const router = useRouter();

    return (
        <div className={`grid ${footerActionGridClassName}`}>
            {/* Conclusion Card */}
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.3 }}
                className="p-5 space-y-4 bg-light-gray g-border global-radius sm:p-8"
            >
                <div className="flex items-center gap-3 mb-2 text-primary">
                    <ConclusionIcon className="w-6 h-6" />
                    <h3 className="text-xl font-bold font-headline">
                        Conclusion
                    </h3>
                </div>
                <p className="font-medium leading-relaxed text-on-surface-variant">
                    {conclusion || 'No conclusion provided.'}
                </p>
            </motion.div>

            {/* Next Visit Card */}
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.4 }}
                className="relative flex flex-col items-center justify-between gap-6 p-5 overflow-hidden text-white shadow-xl bg-primary global global-radius sm:p-8 md:flex-row group"
            >
                <div className="relative z-10 flex items-center w-full gap-4 sm:gap-6 md:w-auto">
                    <div className="p-3 sm:p-4 global-radius bg-light-gray shrink-0">
                        <NextVisitIcon className="h-8 w-7 h-7 sm:w-8 text-primary" />
                    </div>
                    <div>
                        <h3 className="text-xl sm:text-2xl font-bold font-headline mb-0.5 text-white">
                            Next Visit
                        </h3>
                        <p className="text-sm font-medium text-white/70 sm:text-base">
                            {nextVisitDate
                                ? `Scheduled for ${nextVisitDate}`
                                : 'Not Scheduled Yet'}
                        </p>
                    </div>
                </div>

                <Button
                    className={cn(
                        'w-full md:w-auto px-8 py-6 sm:py-7 font-bold btn-primary-cta global-radius',
                        buttonBgColor,
                        buttonTextColor,
                    )}
                    onClick={() => router.push(`/find-doctors/${doctor_id}`)}
                >
                    {buttonText}
                    <ArrowRight className="w-4 h-4 m-0" />
                </Button>

                {/* Decorative background element */}
                <div className="absolute top-0 right-0 w-32 h-32 -mt-16 -mr-16 transition-transform duration-500 rounded-full bg-white/5 group-hover:scale-110" />
            </motion.div>
        </div>
    );
};
