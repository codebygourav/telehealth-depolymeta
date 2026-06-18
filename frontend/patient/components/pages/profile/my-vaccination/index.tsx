import { HelpCircle } from "lucide-react";
import InfoAboutVaccinations from "./info-about-vaccinations";
import ProgressCircleCard from "./progress-circle-card";
import VaccinationSchedule from "./vaccination-schedule";

const MyVaccination = () => {
    return (
        <div className="space-y-8 sm:space-y-10">
            <ProgressCircleCard />

            <VaccinationSchedule />

            <InfoAboutVaccinations />

            <div className="p-4 sm:p-5 md:p-6 bg-primary/5 border border-primary/20 rounded-md flex flex-col sm:flex-row items-start gap-4 sm:gap-6 shadow-sm overflow-hidden relative">
                <div className="absolute top-0 right-0 p-6 sm:p-8 opacity-5 pointer-events-none">
                    <HelpCircle className="w-20 h-20 sm:w-24 sm:h-24" />
                </div>

                <HelpCircle className="w-7 h-7 sm:w-8 sm:h-8 text-primary shrink-0" />

                <div className="min-w-0">
                    <h4 className="font-bold text-[#1F1E1E] text-sm mb-2 uppercase tracking-wide">
                        Clinical Insight
                    </h4>

                    <p className="text-sm text-[#4D4D4D] leading-relaxed">
                        Vaccination schedules are based on international pediatric standards. If you miss a dose, please contact your pediatrician immediately to reschedule. You can add personal notes to each log for tracking side effects or allergic reactions.
                    </p>
                </div>
            </div>
        </div>
    );
};

export default MyVaccination;