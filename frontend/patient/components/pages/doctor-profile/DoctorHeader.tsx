import { Star, Languages as LanguagesIcon, Stethoscope } from 'lucide-react';
import type { DoctorDetailData } from '@/types/doctor-details';


interface DoctorHeaderProps {
    doctor: DoctorDetailData;
}

const DoctorHeader = ({ doctor }: DoctorHeaderProps) => {

    return (
        <section className="rounded-lg p-5 shadow-[0px_2px_4px_0px_#0000001A] border border-[#E7E8EB]">
            <div className="flex flex-col items-center md:flex-row md:items-start gap-6 md:gap-8">

                {/* Avatar Section */}
                <div className="relative group shrink-0">
                    <div className="w-24 h-24 rounded-full">
                        <img
                            src={doctor.profile.avatar}
                            alt={doctor.profile.name}
                            className="w-full h-full rounded-full object-cover"
                            referrerPolicy="no-referrer"
                        />
                    </div>
                </div>

                {/* Info Section */}
                <div className="flex-1 text-center md:text-left space-y-3 sm:space-y-4">

                    {/* department and name */}
                    <div>
                        <p className="text-primary flex items-center justify-center sm:justify-start text-xs font-semibold uppercase tracking-wider">
                            <Stethoscope size={14} color="var(--primary)" className="mr-1.5" />
                            {doctor.profile.department}
                        </p>
                        <h2 className="text-2xl font-bold text-[#1F1E1E] mt-1">
                            {doctor.profile.name}
                        </h2>
                    </div>

                    {/* Experience & Review Cards */}
                    <div className="flex flex-wrap justify-center sm:justify-start gap-3 sm:gap-4 mt-2">

                        {/* Experience Card */}
                        <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                            <p className="text-xs text-[#4D4D4D]">Experience</p>
                            <p className="text-xs font-semibold text-[#4D4D4D]">
                                {doctor.profile.years_experience + "Years Experience" || "N/A"}
                            </p>
                        </div>

                        {/* Languages */}
                        <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                            <LanguagesIcon className="w-4 h-4 shrink-0" />
                            <span className="text-xs text-[#4D4D4D]">
                                {Array.isArray(doctor.languages)
                                    ? doctor.languages.join(', ')
                                    : doctor.languages || 'English'}
                            </span>
                        </div>

                        {/* Review Card */}
                        <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                            <p className="text-xs text-[#4D4D4D] flex items-center gap-x-1">
                                <Star size={14} color="#FABD2E" fill="#FABD2E" />
                                Rating
                            </p>
                            <div className="text-[#4D4D4D] font-bold">
                                {doctor.review_summary?.average_rating || "N/A"}
                                <span className="text-xs text-gray-400"> ({doctor.review_summary?.total_reviews || "0"})</span>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </section>
    );
};

export default DoctorHeader;