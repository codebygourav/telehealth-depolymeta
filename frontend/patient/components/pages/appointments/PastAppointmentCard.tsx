'use client';
import { Calendar, Clock, Star, Video, ChevronRight, FileText } from 'lucide-react';
import { Doctor, Appointment } from '@/types/appointment';
import { useRouter } from 'next/navigation';

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
                return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'cancelled':
                return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
            case 'failed':
                return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
        }
    };

    const router = useRouter();

    const handleViewDetails = (id: string) => {
        router.push(`/appointments/${id}`);
    };
    console.table(appointment);

    return (
        <div className="bg-white dark:bg-gray-900 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
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
                            <h3 className="text-base sm:text-lg md:text-xl font-bold text-primary font-headline leading-tight truncate">
                                {appointment.doctorName}
                            </h3>
                            <p className="text-xs sm:text-sm text-on-surface-variant font-medium truncate">
                                {doctor?.specialty} ({doctor?.experience})
                            </p>
                            <div className="flex flex-wrap items-center gap-2 sm:gap-4 mt-1.5 sm:mt-2">
                                <div className="flex items-center gap-1.5 text-on-surface-variant/70 text-[10px] sm:text-xs font-medium">
                                    <Calendar className="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                                    {appointment.date}
                                </div>
                                <div className="flex items-center gap-1.5 text-on-surface-variant/70 text-[10px] sm:text-xs font-medium">
                                    <Clock className="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                                    {appointment.time}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Right Section - Rating & Status (Stacked vertically) */}
                    <div className="flex flex-col items-end gap-2 shrink-0">
                        <div className="flex items-center gap-1 bg-surface-container-low px-2 py-1 rounded-lg">
                            <Star className="w-3 h-3 fill-primary text-primary" />
                            <span className="text-xs font-bold text-primary">{doctor?.rating}</span>
                        </div>
                        <span className={`px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-bold uppercase tracking-wider ${getStatusColor(appointment.status)}`}>
                            {statusLabel || appointment.status}
                        </span>
                    </div>
                </div>

                {/* Consultation Details Section */}
                <div className="bg-surface-container-lowest/50 rounded-xl sm:rounded-2xl p-3 sm:p-4 border border-outline-variant/5 mb-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-outline-variant/10">
                        <div className="pb-3 sm:pb-0 sm:pr-4">
                            <p className="text-xs font-bold text-primary mb-1.5 sm:mb-2">
                                Consultation Type
                            </p>
                            <div className="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                                {appointment.type === 'video' ? (
                                    <Video className="w-4 h-4" />
                                ) : (
                                    <FileText className="w-4 h-4" />
                                )}
                                <span className="text-xs sm:text-sm font-semibold">{consultationType}</span>
                            </div>
                        </div>
                        <div className="pt-3 sm:pt-0 sm:pl-4">
                            <p className="text-xs font-bold text-primary mb-1.5 sm:mb-2">
                                Consultation Fee
                            </p>
                            <p className="text-base sm:text-lg md:text-xl font-bold text-primary leading-none">
                                ₹{parseFloat(fee).toFixed(2)}
                            </p>
                        </div>
                    </div>
                </div>

                {/* View Details Button */}
                <button
                    onClick={() => onViewDetails(appointment.id)}
                    className="w-full py-2.5 sm:py-3 md:py-3.5 bg-[#0A2E1F] hover:bg-[#0A2E1F]/90 text-white rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all duration-200 active:scale-[0.98]"
                >
                    View Details
                    <ChevronRight className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                </button>
            </div>
        </div>
    );
};

export default PastAppointmentCard;