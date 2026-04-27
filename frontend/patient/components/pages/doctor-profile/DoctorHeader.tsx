import { Star, Verified, Languages as LanguagesIcon } from 'lucide-react';
import type { DoctorDetailData } from '@/types/doctor-details';
import DoctorTags from './DoctorTags';

interface DoctorHeaderProps {
  doctor: DoctorDetailData;
}

const DoctorHeader = ({ doctor }: DoctorHeaderProps) => {
  return (
    <section className="bg-surface-container-lowest rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 border border-outline-variant/10">
      <div className="flex flex-col items-center md:flex-row md:items-start gap-6 md:gap-8">
        {/* Avatar Section */}
        <div className="relative group shrink-0">
          <div className="h-32 w-32 sm:h-36 sm:w-36 md:h-40 md:w-40 rounded-2xl sm:rounded-3xl overflow-hidden shadow-xl ring-4 ring-surface-container">
            <img
              src={doctor.profile.avatar}
              alt={doctor.profile.name}
              className="w-full h-full object-cover"
              referrerPolicy="no-referrer"
            />
          </div>
          <div className="absolute -bottom-2 -right-2 bg-primary text-white px-2 py-1 sm:px-3 sm:py-1 rounded-full text-[10px] sm:text-xs font-bold font-label flex items-center gap-1 shadow-lg">
            <Verified className="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5" />
            {doctor.status}
          </div>
        </div>

        {/* Info Section */}
        <div className="flex-1 text-center md:text-left space-y-3 sm:space-y-4">
          {/* Name and Rating */}
          <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
            <h1 className="text-2xl sm:text-3xl md:text-4xl font-extrabold font-headline tracking-tight text-primary break-words">
              {doctor.profile.name}
            </h1>
            <div className="flex items-center justify-center md:justify-start gap-1 text-on-surface-variant font-medium">
              <Star className="w-4 h-4 sm:w-5 sm:h-5 text-amber-500 fill-current" />
              <span className="text-sm sm:text-base text-on-surface">
                {doctor.review_summary?.average_rating || 0}
              </span>
              <span className="text-xs sm:text-sm opacity-60">
                ({doctor.review_summary?.total_reviews || 0} Reviews)
              </span>
            </div>
          </div>

          {/* Department & Experience */}
          <p className="text-base sm:text-lg md:text-xl text-surface-tint font-medium font-headline break-words">
            {doctor.profile.department} • {doctor.profile.years_experience}+ Years Experience
          </p>

          {/* Languages */}
          <div className="flex items-center justify-center md:justify-start gap-2 text-on-surface-variant font-medium flex-wrap">
            <LanguagesIcon className="w-4 h-4 shrink-0" />
            <span className="text-sm sm:text-base break-words">
              {doctor.languages?.join(', ') || 'English'}
            </span>
          </div>

          {/* Tags */}
          <div className="flex justify-center md:justify-start">
            <DoctorTags specialties={[doctor.profile.department]} />
          </div>
        </div>
      </div>
    </section>
  );
};

export default DoctorHeader;