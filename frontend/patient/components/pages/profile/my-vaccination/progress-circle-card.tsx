import { Card, Skeleton } from "@/components/ui";
import { usePatientVaccinations } from "@/queries/usePatientVaccinations";
import { motion } from "motion/react";


const ProgressCircleCard = () => {
    const { data, isLoading, error } = usePatientVaccinations();

    const summary = data?.data?.vaccination_summary;

    const percentage =
        summary?.completed_percentage || 0;
    
    if (isLoading) {
        return (
            <Card className="rounded-lg p-4 sm:p-5 md:p-6 items-center">
                <div className="flex flex-col items-center space-y-5">
                    <Skeleton className="w-36 h-36 rounded-full" />

                    <div className="space-y-3 flex flex-col items-center">
                        <Skeleton className="h-6 w-52" />
                        <Skeleton className="h-8 w-40 rounded-md" />
                    </div>
                </div>
            </Card>
        );
    }

    if (error) {
        return (
            <Card className="rounded-lg p-4 sm:p-5 md:p-6 items-center">
                <p className="text-sm font-medium text-red-600">
                    Failed to load vaccination summary.
                </p>
            </Card>
        );
    }
    
    return (
        <Card className="rounded-lg p-4 sm:p-5 md:p-6 items-center">
            <div className="relative w-36 h-36 mx-auto">
                <svg className="w-full h-full transform -rotate-90">
                    <circle
                        className="text-surface-container-highest"
                        cx="72" cy="72" fill="transparent" r="62"
                        stroke="#ecf0fb" strokeWidth="12"
                    />
                    <motion.circle
                        initial={{ strokeDashoffset: 390 }}
                        animate={{
                            strokeDashoffset:
                                390 * (1 - percentage / 100),
                        }}
                        className="text-primary"
                        cx="72" cy="72" fill="transparent" r="62"
                        stroke="currentColor" strokeDashoffset="0"
                        strokeDasharray="390" strokeLinecap="round" strokeWidth="12"
                    />
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-4xl font-black text-on-surface leading-none">{percentage}%</span>
                </div>
            </div>
            <div>
                <h3 className="font-semibold text-gray-900 text-lg mb-2">
                    Vaccinations Completed (
                    {summary?.completed_count || 0}/
                    {summary?.total_count || 0}
                    )
                </h3>
                <p className="text-xs px-4 py-1.5 mx-auto rounded-md w-fit bg-gray-100 text-gray-700 border border-gray-200">
                    Next Due: <span className="font-semibold">{summary?.next_due_date || "N/A"}</span>
                </p>
            </div>
        </Card>
    )
}

export default ProgressCircleCard;