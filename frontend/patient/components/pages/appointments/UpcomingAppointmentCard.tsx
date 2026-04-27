'use client';
import { Calendar, Clock, Star, Video, ChevronRight, Phone, Calendar as CalendarIcon } from 'lucide-react';
import { Doctor, Appointment } from '@/types/appointment';
import { useRouter } from 'next/navigation';

interface UpcomingAppointmentCardProps {
    appointment: Appointment;
    doctor: Doctor | undefined;
    onManageClick: (appointmentId: string) => void;
    consultationType?: string;
    fee?: string;
    joinUrl?: string;
    call_now?: boolean;
}

const UpcomingAppointmentCard = ({
    appointment,
    doctor,
    onManageClick,
    consultationType = "Video consultation",
    fee = "0",
    joinUrl,
    call_now
}: UpcomingAppointmentCardProps) => {

    const router = useRouter();

    return (
        <div className="bg-white rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div className="p-4 sm:p-5 md:p-6">
                {/* Header Section - Doctor Info */}
                <div className="flex sm:flex-row sm:justify-between sm:items-start gap-4 mb-4 sm:mb-5 md:mb-6">
                    <div className="flex gap-3 sm:gap-4">
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
                            <h3 className="text-base sm:text-lg md:text-xl font-bold text-primary font-headline leading-tight break-words">
                                {appointment.doctorName}
                            </h3>
                            <p className="text-xs sm:text-sm text-on-surface-variant font-medium mt-0.5 break-words">
                                {doctor?.specialty} ({doctor?.experience})
                            </p>
                            <div className="flex flex-wrap items-center gap-2 sm:gap-3 md:gap-4 mt-1.5 sm:mt-2">
                                <div className="flex items-center gap-1 text-on-surface-variant/70 text-[10px] sm:text-xs font-bold">
                                    <Calendar className="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                                    {appointment.date}
                                </div>
                                <div className="flex items-center gap-1 text-on-surface-variant/70 text-[10px] sm:text-xs font-bold">
                                    <Clock className="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                                    {appointment.time}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Rating Badge */}
                    {doctor?.rating !== 0 && doctor?.rating && (
                        <div className="flex items-center gap-1 bg-surface-container-low px-2 py-1 rounded-lg self-start sm:self-auto">
                            <Star className="w-3 h-3 sm:w-3.5 sm:h-3.5 fill-primary text-primary" />
                            <span className="text-xs sm:text-sm font-bold text-primary">{doctor?.rating}</span>
                        </div>
                    )}
                </div>

                {/* Consultation Details Section */}
                <div className="bg-surface-container-lowest/50 rounded-xl sm:rounded-2xl p-3 sm:p-4 border border-outline-variant/5 mb-4 sm:mb-5">
                    <div className="flex flex-col sm:flex-row sm:divide-x sm:divide-outline-variant/10">
                        <div className="flex-1 pb-3 sm:pb-0 sm:pr-4">
                            <p className="text-[10px] sm:text-xs font-bold text-primary mb-1.5 sm:mb-2">
                                Consultation Type
                            </p>
                            <div className="flex items-center gap-1.5 sm:gap-2 text-emerald-600">
                                {appointment.type === 'video' ? (
                                    <Video className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                ) : (
                                    <CalendarIcon className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                )}
                                <span className="text-xs sm:text-sm font-bold break-words">{consultationType}</span>
                            </div>
                        </div>
                        <div className="flex-1 pt-3 sm:pt-0 sm:pl-4 border-t sm:border-t-0 border-outline-variant/10">
                            <p className="text-[8px] sm:text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider mt-1">
                                Consultation Fee
                            </p>
                            <p className="text-base sm:text-lg md:text-xl mt-2 font-bold text-primary leading-none">
                                ₹{parseFloat(fee).toFixed(2)}
                            </p>
                        </div>
                    </div>
                </div >
                <div className="flex gap-3">
                    {call_now && joinUrl ? (
                        <button
                            onClick={() => window.open(`/start-consultation?room_url=${joinUrl}&appointment_id=${appointment.id}`, "_blank")}
                            className="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-bold text-sm flex items-center justify-center gap-2 hover:bg-emerald-700 transition-all"
                        >
                            <Phone className="w-4 h-4" />
                            Join Now
                        </button>
                    ) : (
                        <button
                            onClick={() => router.push(`/appointments/manage-appointment/${appointment.id}`)}
                            className="flex-1 py-3.5 bg-[#0A2E1F] text-white rounded-xl font-bold text-sm flex items-center justify-center gap-2 hover:opacity-90 transition-all"
                        >
                            Manage Appointment
                            <ChevronRight className="w-4 h-4" />
                        </button>
                    )}
                </div>
            </div >
        </div >
    );
};

export default UpcomingAppointmentCard;