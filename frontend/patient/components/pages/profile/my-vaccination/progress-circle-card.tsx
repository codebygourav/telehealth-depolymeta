import { Card } from "@/components/ui";
import { motion } from "motion/react";


const ProgressCircleCard = () => {
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
                        animate={{ strokeDashoffset: 390 * (1 - 0.75) }}
                        className="text-primary"
                        cx="72" cy="72" fill="transparent" r="62"
                        stroke="currentColor" strokeDashoffset="0"
                        strokeDasharray="390" strokeLinecap="round" strokeWidth="12"
                    />
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-4xl font-black text-on-surface leading-none">75%</span>
                </div>
            </div>
            <div>
                <h3 className="font-semibold text-gray-900 text-lg mb-2">Vaccinations Completed</h3>
                <p className="text-xs px-4 py-1.5 mx-auto rounded-md w-fit bg-gray-100 text-gray-700 border border-gray-200">
                    Next Due: <span className="font-semibold">15 Oct 2023</span>
                </p>
            </div>
        </Card>
    )
}

export default ProgressCircleCard;