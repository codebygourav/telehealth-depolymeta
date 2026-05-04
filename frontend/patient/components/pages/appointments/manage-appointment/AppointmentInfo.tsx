"use client"

import { Card, CardContent } from "@/components/ui";
import { Calendar, User } from "lucide-react";
import { getStatusColor } from "@/src/utils/getStatusColor";

interface AppointmentInfoProps {
    date?: string;
    time?: string;
    booking_type?: string;
    consultation_type?: string;
    patient_name?: string;
    patient_age?: string;
    patient_gender?: string;
    patient_phone?: string;
    patient_email?: string;
    patient_blood_group?: string;
}

export default function AppointmentInfo({
    date,
    time,
    booking_type,
    consultation_type,
    patient_name,
    patient_age,
    patient_gender,
    patient_phone,
    patient_email,
    patient_blood_group,
}: AppointmentInfoProps) {
    return (
        <div className="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-5 md:gap-6">

            {/* Appointment Details */}
            <Card className="rounded-lg p-5">
                <CardContent className="p-0 space-y-5">
                    <h3 className="text-lg text-[#1F1E1E] font-semibold flex items-center gap-2.5">
                        <Calendar size={18} color='#055BD9' />
                        Appointment Details
                    </h3>
                    <div className="space-y-2.5 sm:space-y-3">
                        <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                            <span className="text-xs sm:text-sm text-[#4D4D4D]">Date</span>
                            <span className="text-xs sm:text-sm font-medium text-[#4D4D4D]">
                                {date}
                            </span>
                        </div>
                        <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                            <span className="text-xs sm:text-sm text-[#4D4D4D]">Time</span>
                            <span className="text-xs sm:text-sm font-medium text-[#4D4D4D]">
                                {time}
                            </span>
                        </div>
                        <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                            <span className="text-xs sm:text-sm text-[#4D4D4D]">Consultation Type</span>
                            <span
                                className={`text-[10px] sm:text-xs px-2 py-1 rounded-full w-fit ${getStatusColor(
                                    "session",
                                    consultation_type
                                )}`}
                            >
                                {consultation_type || "N/A"}
                            </span>
                        </div>
                        <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                            <span className="text-xs sm:text-sm text-[#4D4D4D]">Booking Type</span>
                            <span className="text-xs sm:text-sm font-medium text-[#4D4D4D]">
                                {booking_type || "N/A"}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Patient Details */}
            <Card className="rounded-lg p-5">
                <CardContent className="p-0 space-y-5">
                    <h3 className="text-lg text-[#1F1E1E] font-semibold flex items-center gap-2.5">
                        <User size={18} color='#055BD9' />
                        Patient Details
                    </h3>
                    <div className="space-y-2.5 sm:space-y-3">
                        <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                            <span className="text-xs sm:text-sm text-[#4D4D4D]">Name</span>
                            <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                {patient_name}
                            </span>
                        </div>
                        <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                            <span className="text-xs sm:text-sm text-[#4D4D4D]">Age</span>
                            <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                {patient_age}
                            </span>
                        </div>
                        <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                            <span className="text-xs sm:text-sm text-[#4D4D4D]">Gender</span>
                            <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                {patient_gender}
                            </span>
                        </div>
                        {patient_blood_group && (
                            <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                <span className="text-xs sm:text-sm text-[#4D4D4D]">Blood Group</span>
                                <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">{patient_blood_group}</span>
                            </div>
                        )}
                        <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                            <span className="text-xs sm:text-sm text-[#4D4D4D]">Phone</span>
                            <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">{patient_phone || "N/A"}</span>
                        </div>
                        <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                            <span className="text-xs sm:text-sm text-[#4D4D4D]">Email</span>
                            <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white break-all text-left sm:text-right">{patient_email || "N/A"}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
