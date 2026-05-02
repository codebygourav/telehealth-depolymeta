"use client";
import { LanguagesIcon, Star, Stethoscope } from "lucide-react";
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
        {/* <img
          src={
            doctor.avatar ||
            "https://api.dicebear.com/7.x/avataaars/svg?seed=Felix"
          }
          alt="
          className="w-[105px] h-[105px] rounded-full object-cover shrink-0"
        /> */}


      </div>

      <div className="flex flex-col items-center md:flex-row md:items-start gap-6 md:gap-8">

        {/* Avatar Section */}
        <div className="relative group shrink-0">
          <div className="w-24 h-24 rounded-full">
            <img
              src={doctor.avatar}
              alt={doctor.name}
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
              <Stethoscope size={14} color="#055BD9" className="mr-1.5" />
              {doctor.department}
            </p>
            <h2 className="text-2xl font-bold text-[#1F1E1E] mt-1">
              {doctor.name}
            </h2>
          </div>

          {/* Experience & Review Cards */}
          <div className="flex flex-wrap justify-center sm:justify-start gap-3 sm:gap-4 mt-2">

            {/* Experience Card */}
            <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
              <p className="text-xs text-[#4D4D4D]">Experience</p>
              <p className="text-xs font-semibold text-[#4D4D4D]">
                {doctor.years_experience + "Years Experience" || "N/A"}
              </p>
            </div>



            {/* Review Card */}
            <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
              <p className="text-xs text-[#4D4D4D] flex items-center gap-x-1">
                <Star size={14} color="#FABD2E" fill="#FABD2E" />
                Rating
              </p>
              <div className="text-[#4D4D4D] font-bold">
                {/* {doctor.review_summary?.average_rating || "N/A"} */}
                {/* <span className="text-xs text-gray-400"> ({doctor.review_summary?.total_reviews || "0"})</span> */}
              </div>
            </div>

          </div>

        </div>
      </div>

      {/* <div className="flex flex-row items-center justify-between mt-3">
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
      </div> */}
    </motion.div>
  );
}
