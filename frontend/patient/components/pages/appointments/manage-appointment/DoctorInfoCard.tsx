"use client";
import { Star, Stethoscope } from "lucide-react";
import { motion } from "motion/react";

import type { AppointmentDoctor } from "@/types/appointment-summary";
import { CustomAvatar } from "@/components/custom/custom-avatar";

interface DoctorInfoCardProps {
  doctor: AppointmentDoctor | undefined;
  appointmentStatus: string;
}

export default function DoctorInfoCard({
  doctor,
  appointmentStatus,
}: DoctorInfoCardProps) {
  if (!doctor) return null;
  console.log(appointmentStatus);
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className=" rounded-[40px] p-8 shadow-sm border border-outline-variant/10"
    >
      <div className="flex flex-row gap-4 items-left text-center">
        <img
          src={
            doctor.avatar ||
            "https://api.dicebear.com/7.x/avataaars/svg?seed=Felix"
          }
          alt="Profile"
          className="w-[105px] h-[105px] rounded-full object-cover shrink-0"
        />
        <div>
          <div className="flex items-center gap-2 text-emerald-600 mb-2">
            <Stethoscope className="w-4 h-4" />
            <span className="text-xs font-bold uppercase tracking-widest">
              {doctor.department}
            </span>
          </div>
          <h2 className="text-2xl font-bold font-headline text-primary">
            {doctor.name}
          </h2>
          <div className="flex flex-row gap-4 items-center">
        {/* Experience */}
        <div className="flex items-center gap-2 text-muted-foreground text-xs px-3 py-1 bg-slate-50 rounded-md border border-slate-200">
          <span className="font-semibold text-slate-700">Experience: </span>
          <span className="font-bold">{doctor.years_experience ?? "N/A"}</span>
        </div>
        {/* Rating */}
        <div className="flex items-center gap-2 text-muted-foreground text-xs px-3 py-1 bg-slate-50 rounded-md border border-slate-200">
        <Star className="w-4 h-4" fill="#facc15" />
          <span className="font-semibold">Rating: </span>
          <span className="font-bold">{doctor.average_rating ?? "N/A"}</span>
        </div>
      </div>
        </div>
      </div>
     
      <div className="flex flex-row items-center justify-between mt-3">
        <span
          className={`text-xs font-semibold px-3 py-1 rounded-full border ${
            appointmentStatus === "Completed"
              ? "text-green-700 bg-green-50 border-green-200"
              : appointmentStatus === "Cancelled"
                ? "text-red-700 bg-red-50 border-red-200"
                : "text-blue-700 bg-blue-50 border-blue-200"
          }`}
        >
          {appointmentStatus}
        </span>
      </div>
    </motion.div>
  );
}
