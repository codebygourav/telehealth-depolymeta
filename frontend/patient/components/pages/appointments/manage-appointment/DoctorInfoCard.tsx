"use client"
import { Stethoscope } from 'lucide-react';
import { motion } from 'motion/react';

import type { AppointmentDoctor } from '@/types/appointment-summary';

interface DoctorInfoCardProps {
    doctor: AppointmentDoctor | undefined;
}

export default function DoctorInfoCard({ doctor }: DoctorInfoCardProps) {
    if (!doctor) return null;

    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="bg-white rounded-[40px] p-8 shadow-sm border border-outline-variant/10"
        >
            <div className="flex flex-col items-center text-center">
                <div className="w-32 h-32 rounded-full overflow-hidden mb-6 ring-4 ring-emerald-50 shadow-lg">
                    <img
                        src={doctor.avatar}
                        alt={doctor.name}
                        className="w-full h-full object-cover"
                        referrerPolicy="no-referrer"
                    />
                </div>
                <div className="flex items-center gap-2 text-emerald-600 mb-2">
                    <Stethoscope className="w-4 h-4" />
                    <span className="text-xs font-bold uppercase tracking-widest">{doctor.department}</span>
                </div>
                <h2 className="text-2xl font-bold font-headline text-primary">{doctor.name}</h2>
            </div>
        </motion.div>
    );
}
