import BabyProfileCard from './baby-profile-card';
import ProgressCircleCard from './progress-circle-card';
import VaccinationSchedule from './vaccination-schedule';
import InfoAboutVaccinations from './info-about-vaccinations';
import { HelpCircle } from 'lucide-react';

const MyVaccination = () => {
    return (
        <>
            <div className="flex gap-6 mb-10">

                {/* Baby Profile Card */}
                <BabyProfileCard />

                {/* Progress Circle Card */}
                <ProgressCircleCard />

            </div>

            <VaccinationSchedule />

            <InfoAboutVaccinations />

            {/* Clinical Insight */}
            <div className="p-6 bg-primary/5 border border-primary/20 rounded-md flex flex-col sm:flex-row items-start gap-6 shadow-sm overflow-hidden relative mt-10">
                <div className="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
                    <HelpCircle className="w-24 h-24" />
                </div>
                <HelpCircle className="w-8 h-8 text-primary shrink-0" />
                <div>
                    <h4 className="font-bold text-[#1F1E1E] text-sm mb-2 uppercase tracking-wide">Clinical Insight</h4>
                    <p className="text-sm text-[#4D4D4D]">
                        Vaccination schedules are based on international pediatric standards. If you miss a dose, please contact your pediatrician immediately to reschedule. You can add personal notes to each log for tracking side effects or allergic reactions.
                    </p>
                </div>
            </div>
        </>
    )
}

export default MyVaccination;