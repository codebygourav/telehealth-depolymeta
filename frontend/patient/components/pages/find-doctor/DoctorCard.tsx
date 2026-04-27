import { Star, Clock, Languages, MapPin } from 'lucide-react';
import type { Doctor } from '@/types/browse-doctors';
import { Button } from '@/components/ui/button';
import { useRouter } from 'next/navigation';

interface DoctorCardProps {
    doctor: Doctor;
    onBook: () => void;
    onError?: () => void;
    onSuccess?: () => void;
    isLoading?: boolean;
}

const DoctorCard = ({ doctor, onBook, onError, onSuccess, isLoading = false }: DoctorCardProps) => {
    const router = useRouter();

    const handleBookNow = () => {
        router.push(`/find-doctors/${doctor.id}`);
    };

    return (
        <div className="bg-surface-container-lowest rounded-2xl sm:rounded-3xl shadow-sm border border-outline-variant/10 hover:shadow-md transition-shadow group h-full flex flex-col overflow-hidden">

            {/* MOBILE LAYOUT (visible only on mobile) */}
            <div className="sm:hidden">
                {/* Mobile Header */}
                <div className="bg-muted/10 p-4">
                    <div className="flex gap-3">
                        <img
                            src={doctor.avatar}
                            alt={doctor.name}
                            className="w-14 h-14 rounded-xl object-cover shrink-0"
                            referrerPolicy="no-referrer"
                        />
                        <div className="flex-1 min-w-0">
                            {/* Name and Rating */}
                            <div className="flex flex-wrap items-start justify-between gap-1">
                                <h3 className="font-headline font-bold text-base text-primary-container break-words flex-1">
                                    {doctor.name}
                                </h3>
                                {doctor?.rating ? (
                                    <div className="flex items-center gap-1 bg-secondary-container px-1.5 py-0.5 rounded-lg shrink-0">
                                        <Star className="w-2.5 h-2.5 text-on-secondary-container fill-current" />
                                        <span className="text-[10px] font-bold text-on-secondary-container">
                                            {doctor.rating}
                                        </span>
                                    </div>
                                ) : null}
                            </div>
                            {/* Category */}
                            <p className="text-on-primary-container font-semibold text-xs mt-0.5 truncate">
                                {Array.isArray(doctor.speciality) && doctor.speciality.length > 0
                                    ? doctor.speciality[0].name
                                    : ""}
                            </p>
                        </div>
                    </div>
                    {/* Experience & Languages - Full width on mobile */}
                    <div className="w-full flex flex-wrap items-center gap-2 text-[10px] text-on-surface-variant font-medium mt-3 pt-2 border-gray-100">
                        <span className="flex items-center gap-1">
                            <Clock className="w-3 h-3" />
                            Exp: {doctor.years_experience} yrs
                        </span>
                        <span className="flex items-center gap-1">
                            <Languages className="w-3 h-3" />
                            Lang: {doctor.languages_known?.join(', ') || 'N/A'}
                        </span>
                    </div>
                </div>

                {/* Mobile Consultation Details */}
                <div className="p-4 pt-3">
                    <div className="flex flex-col gap-3">
                        <div>
                            <p className="text-[10px] uppercase tracking-wider font-bold text-on-surface-variant mb-0.5">
                                Consultation Type
                            </p>
                            <div className="flex items-center gap-1.5 text-emerald-600">
                                <MapPin className="w-3 h-3 shrink-0" />
                                <p className="text-xs font-bold capitalize break-words">
                                    {Array.isArray(doctor.consultation_type_label)
                                        ? doctor.consultation_type_label?.join(' / ')
                                        : doctor.consultation_type_label}
                                </p>
                            </div>
                        </div>
                        <div>
                            <p className="text-[10px] uppercase tracking-wider font-bold text-on-surface-variant mb-0.5">
                                Consultation Fee
                            </p>
                            <p className="text-base font-bold text-primary-container">
                                ₹{doctor.consultation_fee}
                            </p>
                        </div>
                    </div>
                    <div className="pt-3">
                        <Button
                            onClick={handleBookNow}
                            disabled={isLoading}
                            variant="default"
                            className="w-full py-2.5 text-xs font-bold bg-primary text-primary-foreground hover:bg-primary/90 rounded-xl transition-all shadow-md"
                        >
                            Book Now
                        </Button>
                    </div>
                </div>
            </div>

            {/* DESKTOP LAYOUT (visible only on tablet and above) */}
            <div className="hidden sm:block">
                {/* Desktop Header */}
                <div className="p-5 md:p-6">
                    <div className="flex gap-4 md:gap-5">
                        <img
                            src={doctor.avatar}
                            alt={doctor.name}
                            className="w-[72px] h-[72px] md:w-20 md:h-20 rounded-xl md:rounded-2xl object-cover shrink-0"
                            referrerPolicy="no-referrer"
                        />
                        <div className="flex-grow min-w-0">
                            {/* Name and Rating */}
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <h3 className="font-headline font-bold text-xl md:text-2xl text-primary-container break-words flex-1">
                                    {doctor.name}
                                </h3>
                                {doctor?.rating ? (
                                    <div className="flex items-center gap-1 bg-secondary-container px-2 py-1 rounded-lg shrink-0">
                                        <Star className="w-3.5 h-3.5 md:w-4 md:h-4 text-on-secondary-container fill-current" />
                                        <span className="text-xs font-bold text-on-secondary-container">
                                            {doctor.rating}
                                        </span>
                                    </div>
                                ) : null}
                            </div>
                            {/* Category */}
                            <p className="text-on-primary-container font-semibold text-sm md:text-base mt-1 truncate">
                                {Array.isArray(doctor.speciality) && doctor.speciality.length > 0
                                    ? doctor.speciality[0].name
                                    : ""}
                            </p>
                            {/* Experience & Languages - Inline on desktop */}
                            <div className="flex flex-wrap items-center gap-4 md:gap-6 text-xs md:text-sm text-on-surface-variant font-medium mt-2">
                                <span className="flex items-center gap-1">
                                    <Clock className="w-3.5 h-3.5 md:w-4 md:h-4" />
                                    Exp: {doctor.years_experience} yrs
                                </span>
                                <span className="flex items-center gap-1">
                                    <Languages className="w-3.5 h-3.5 md:w-4 md:h-4" />
                                    Lang: {doctor.languages_known?.join(', ') || 'N/A'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Desktop Consultation Details */}
                <div className="p-5 md:p-6 pt-0 md:pt-5 mt-auto">
                    <div className="flex flex-row gap-6 md:gap-8 mb-5 md:mb-6">
                        <div className="flex-1">
                            <p className="text-[10px] md:text-xs uppercase tracking-wider font-bold text-on-surface-variant mb-1">
                                Consultation Type
                            </p>
                            <div className="flex items-center gap-1.5 text-emerald-600">
                                <MapPin className="w-3.5 h-3.5 md:w-4 md:h-4 shrink-0" />
                                <p className="text-sm md:text-base font-bold capitalize break-words">
                                    {Array.isArray(doctor.consultation_type_label)
                                        ? doctor.consultation_type_label?.join(' / ')
                                        : doctor.consultation_type_label}
                                </p>
                            </div>
                        </div>
                        <div className="flex-1">
                            <p className="text-[10px] md:text-xs uppercase tracking-wider font-bold text-on-surface-variant mb-1">
                                Consultation Fee
                            </p>
                            <p className="text-lg md:text-xl font-bold text-primary-container">
                                ₹{doctor.consultation_fee}
                            </p>
                        </div>
                    </div>

                    <Button
                        onClick={handleBookNow}
                        disabled={isLoading}
                        variant="default"
                        className="w-full py-4 md:py-5 text-sm md:text-base font-bold bg-primary text-primary-foreground hover:bg-primary/90 rounded-xl transition-all shadow-md"
                    >
                        Book Now
                    </Button>
                </div>
            </div>
        </div>
    );
};

export default DoctorCard;