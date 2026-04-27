"use client"
import { motion } from 'motion/react';

interface AppointmentInfoProps {
    date?: string;
    time?: string;
    booking_type?: string;
    patient_name?: string;
    patient_age?: string;
    patient_gender?: string;
    appointment_status?: string;
}

export default function AppointmentInfo({
    date,
    time,
    booking_type,
    patient_name,
    patient_age,
    patient_gender,
    appointment_status
}: AppointmentInfoProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.1 }}
            className="bg-white rounded-[40px] p-8 shadow-sm border border-outline-variant/10 space-y-8"
        >
            {/* Appointment Details */}
            <section>
                <div className="flex md:items-center items-start justify-between mb-6">
                    <h3 className="text-lg font-bold font-headline text-primary">Appointment Details</h3>
                    <span className="px-3 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-[10px] font-bold uppercase tracking-widest border border-emerald-100">
                        {appointment_status}
                    </span>
                </div>
                <div className="space-y-4">
                    <div className="flex justify-between items-center py-3 border-b border-outline-variant/5">
                        <span className="text-on-surface-variant font-medium text-sm">Date</span>
                        <span className="font-bold text-primary text-sm">{date}</span>
                    </div>
                    <div className="flex justify-between items-center py-3 border-b border-outline-variant/5">
                        <span className="text-on-surface-variant font-medium text-sm">Time</span>
                        <span className="font-bold text-primary text-sm">{time}</span>
                    </div>
                    <div className="flex justify-between items-center py-3">
                        <span className="text-on-surface-variant font-medium text-sm">Booking Type</span>
                        <span className="font-bold text-primary text-sm">{booking_type}</span>
                    </div>
                </div>
            </section>

            {/* Patient Details */}
            <section>
                <h3 className="text-lg font-bold font-headline text-primary mb-6">Patient Details</h3>
                <div className="space-y-4">
                    <div className="flex justify-between items-center py-3 border-b border-outline-variant/5">
                        <span className="text-on-surface-variant font-medium text-sm">Name</span>
                        <span className="font-bold text-primary text-sm">{patient_name}</span>
                    </div>
                    <div className="flex justify-between items-center py-3 border-b border-outline-variant/5">
                        <span className="text-on-surface-variant font-medium text-sm">Age</span>
                        <span className="font-bold text-primary text-sm">{patient_age}</span>
                    </div>
                    <div className="flex justify-between items-center py-3">
                        <span className="text-on-surface-variant font-medium text-sm">Gender</span>
                        <span className="font-bold text-primary text-sm">{patient_gender}</span>
                    </div>
                </div>
            </section>
        </motion.div>
    );
}
