import { Star, Clock, Languages, MapPin, ChevronRight, Video, Hospital } from 'lucide-react';
import type { Doctor } from '@/types/browse-doctors';
import { Button } from '@/components/ui/button';
import { useRouter } from 'next/navigation';

interface DoctorCardProps {
    doctor: Doctor;
    isLoading?: boolean;
}

const DoctorCard = ({ doctor, isLoading = false }: DoctorCardProps) => {

    const router = useRouter();

    const handleBookNow = () => {
        router.push(`/find-doctors/${doctor.id}`);
    };

    return (
        <div className="shadow-[0px_2px_4px_rgba(0,0,0,0.1)] rounded-lg border border-light-gray transition-shadow group h-full flex flex-col overflow-hidden">

            {/* DESKTOP LAYOUT (visible only on tablet and above) */}
            <div className="">

                {/* Desktop Header */}
                <div className="p-5">

                    <div className="flex gap-4">
                        <img
                            src={doctor.avatar}
                            alt={doctor.name}
                            className="w-[72px] h-[72px] md:w-20 md:h-20 rounded-full object-cover shrink-0"
                            referrerPolicy="no-referrer"
                        />
                        <div className="flex-grow min-w-0">

                            {/* Name and Rating */}
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <h3 className="font-semibold text-lg md:text-xl text-black break-words flex-1">
                                    {doctor.name}
                                </h3>
                                {doctor?.rating ? (
                                    <div className="flex items-center gap-1 bg-primary/8 text-primary px-2 py-1.5 rounded shrink-0">
                                        <Star size={12} color="#055bd9" fill="#055bd9" />
                                        <span className="text-xs font-semibold">
                                            {doctor.rating}
                                        </span>
                                    </div>
                                ) : null}
                            </div>

                            {/* Category */}
                            <p className="font-medium text-sm text-[#4D4D4D] md:text-base mt-1">
                                {Array.isArray(doctor.speciality) && doctor.speciality.length > 0
                                    ? (typeof doctor.speciality[0] === 'string' ? doctor.speciality[0] : doctor.speciality[0].name)
                                    : (typeof doctor.speciality === 'string' ? doctor.speciality : (doctor.speciality as any)?.name || "")}
                            </p>

                            {/* Experience & Languages - Inline on desktop */}
                            <div className="flex flex-wrap items-center gap-4 md:gap-6 text-xs md:text-sm text-on-surface-variant font-medium mt-2">
                                <span className="flex items-center gap-1.5 text-xs font-medium text-[#4D4D4D]">
                                    <Clock size={14} />
                                    Exp: {doctor.years_experience} yrs
                                </span>
                                <span className="flex items-center gap-1.5 text-xs font-medium text-[#4D4D4D]">
                                    <Languages size={14} />
                                    Lang: {doctor.languages_known?.join(', ') || 'N/A'}
                                </span>
                            </div>

                        </div>
                    </div>

                    {/* Desktop Consultation Details */}
                    <div className="p-5 bg-light-gray mt-5">
                        <div className="flex md:flex-row flex-col gap-6 md:gap-8 mb-5 md:mb-6 relative">

                            <div className="flex-1 md:text-right text-left flex md:flex-col flex-row md:items-start item-center justify-between">
                                <p className="text-sm font-semibold text-black">
                                    Consultation Type
                                </p>
                                <div className="flex items-center gap-1.5 md:mt-1.5">
                                    {
                                        doctor.consultation_type === 'video' || doctor.consultation_type === 'Video' ? (
                                            <Video size={18} color='#18CE1E' fill="#18CE1E" />
                                        ) : doctor.consultation_type === 'in-person' || doctor.consultation_type === 'In Person' ? (
                                            <Hospital size={18} color='#18CE1E' />
                                        ) : (
                                            <>
                                                <Video size={18} color='#18CE1E' fill="#18CE1E" />
                                                <Hospital size={18} color='#18CE1E' />
                                            </>
                                        )
                                    }
                                    <p className="text-xs font-bold capitalize break-words">
                                        {doctor.consultation_type_label}
                                    </p>
                                </div>
                            </div>

                            <div className='absolute left-1/2 top-0 w-px h-10 mx-auto bg-[#E7E8EB] md:block hidden'></div>

                            <div className="flex-1 md:text-right text-left flex md:flex-col flex-row md:items-end item-center justify-between">
                                <p className="text-sm font-semibold text-black">
                                    Consultation Fee
                                </p>
                                <p className="text-xs font-semibold text-black md:mt-1.5">
                                    ₹{doctor.consultation_fee}
                                </p>
                            </div>

                        </div>

                        <Button
                            onClick={handleBookNow}
                            disabled={isLoading}
                            variant="default"
                            className="w-full py-3.5 h-auto text-sm font-semibold"
                        >
                            Book Your Appointment
                            <ChevronRight size={22} strokeWidth={3.5} />
                        </Button>
                    </div>

                </div>


            </div>

        </div>
    );
};

export default DoctorCard;