'use client';
import { Calendar, Clock, Star, Video, ChevronRight, Hospital } from 'lucide-react';
import { Doctor, Appointment } from '@/types/appointment';
import { Button } from '@/components/ui';

interface PastAppointmentCardProps {
    appointment: Appointment;
    doctor: Doctor | undefined;
    onViewDetails: (appointmentId: string) => void;
    consultationType?: string;
    fee?: string;
    statusLabel?: string;
}

const PastAppointmentCard = ({
    appointment,
    doctor,
    onViewDetails,
    consultationType = "Video consultation",
    fee = "0",
    statusLabel
}: PastAppointmentCardProps) => {

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed':
                return 'bg-[#F4FBF7] text-[#18CE1E] dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'cancelled':
                return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
            case 'failed':
                return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
        }
    };

    return (
        <div className="bg-white shadow-[0px_2px_4px_rgba(0,0,0,0.1)] rounded-lg border border-light-gray overflow-hidden transition-all duration-300">
            <div className="p-4 sm:p-5 md:p-6">

                {/* Header Section - Doctor Info with Rating & Status on Top Right */}
                <div className="flex justify-between items-start gap-4 mb-4">

                    {/* Left Section - Doctor Info */}
                    <div className="flex gap-3 sm:gap-4 flex-1 min-w-0">
                        <div className="relative shrink-0">
                            <div className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-full overflow-hidden border-2 border-surface-container-low">
                                <img
                                    src={appointment.doctorImage}
                                    alt={appointment.doctorName}
                                    className="w-full h-full object-cover"
                                    referrerPolicy="no-referrer"
                                    onError={(e) => {
                                        e.currentTarget.src = '/default-avatar.png';
                                    }}
                                />
                            </div>
                        </div>
                        <div className="flex-1 min-w-0">
                            <h3 className="font-semibold text-lg md:text-xl text-black break-words flex-1">
                                {appointment.doctorName}
                            </h3>
                            <p className="text-sm text-[#4D4D4D] font-medium">
                                {doctor?.specialty} ({doctor?.experience})
                            </p>
                            <div className="flex flex-wrap items-center gap-2 sm:gap-4 mt-1.5 sm:mt-2">
                                <div className="flex items-center gap-1.5 text-[#4D4D4D] text-xs font-medium">
                                    <Calendar size={14} color='#4D4D4D' />
                                    {appointment.date}
                                </div>
                                <div className="flex items-center gap-1.5 text-[#4D4D4D] text-xs font-medium">
                                    <Clock size={14} color='#4D4D4D' />
                                    {appointment.time}
                                </div>
                            </div>
                            <span className={`px-3 py-1.5 rounded text-xs font-semibold md:hidden inline-block mt-2 ${getStatusColor(appointment.status)}`}>
                                {statusLabel || appointment.status}
                            </span>
                        </div>
                    </div>

                    {/* Right Section - Rating & Status (Stacked vertically) */}
                    <div className="flex flex-col items-end gap-2 shrink-0">
                        <div className="flex items-center gap-1 bg-primary/8 text-primary px-2 py-1.5 rounded h-fit">
                            <Star size={12} color="var(--primary)" fill="var(--primary)" />
                            <span className="text-xs font-semibold">{doctor?.rating}</span>
                        </div>
                        <span className={`px-3 py-1.5 rounded text-xs font-semibold hidden md:block ${getStatusColor(appointment.status)}`}>
                            {statusLabel || appointment.status}
                        </span>
                    </div>
                </div>

                {/* Consultation Details Section */}
                <div className="p-5 bg-light-gray mt-5">

                    <div className="flex md:flex-row flex-col gap-6 md:gap-8 mb-5 md:mb-6 relative">

                        <div className="flex-1 md:text-right text-left flex md:flex-col flex-row md:items-start item-center justify-between">
                            <p className="text-sm font-semibold text-black">
                                Consultation Type
                            </p>
                            <div className="flex items-center gap-1.5 md:mt-1.5">
                                {
                                    appointment.type === 'video' ? (
                                        <Video size={18} color='#18CE1E' fill="#18CE1E" />
                                    ) : appointment.type === 'in-person' ? (
                                        <Hospital size={18} color='#18CE1E' />
                                    ) : (
                                        <>
                                            <Video size={18} color='#18CE1E' fill="#18CE1E" />
                                            <Hospital size={18} color='#18CE1E' />
                                        </>
                                    )
                                }
                                <p className="text-xs font-bold capitalize break-words hidden md:block">
                                    {consultationType}
                                </p>
                                <p className="text-xs font-bold capitalize break-words md:hidden">
                                    {consultationType === "Video consultation" ? "Video" : "In Person"}
                                </p>
                            </div>
                        </div>

                        <div className='absolute left-1/2 top-0 w-px h-10 mx-auto bg-[#E7E8EB] md:block hidden'></div>

                        <div className="flex-1 md:text-right text-left flex md:flex-col flex-row md:items-end item-center justify-between">
                            <p className="text-sm font-semibold text-black">
                                Consultation Fee
                            </p>
                            <p className="text-xs font-semibold text-black md:mt-1.5">
                                ₹{parseFloat(fee).toFixed(2)}
                            </p>
                        </div>

                    </div>

                    {/* View Details Button */}
                    <Button
                        onClick={() => onViewDetails(appointment.id)}
                        variant="default"
                        className="w-full  h-auto text-sm font-semibold btn-primary-cta"
                    >
                        View Details
                        <ChevronRight size={22} strokeWidth={3.5} className="m-0" />
                    </Button>
                </div>

            </div>
        </div>
    );
};

export default PastAppointmentCard;