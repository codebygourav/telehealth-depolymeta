"use client";
import { Star, Stethoscope } from "lucide-react";
import type { AppointmentDoctor } from "@/types/appointment-summary";
import { getStatusColor } from "@/src/utils/getStatusColor";
import { Card, CardContent } from "@/components/ui";

interface DoctorInfoCardProps {
    doctor: AppointmentDoctor | undefined;
    appointment_status: string;
}

export default function DoctorInfoCard({ doctor, appointment_status }: DoctorInfoCardProps) {

    if (!doctor) return null;

    return (
        <Card className="rounded-lg p-4 sm:p-5 md:p-6">
            <CardContent className="p-0">
                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">

                    <div className="flex flex-col sm:flex-row gap-4 sm:gap-5 flex-1">
                        <img
                            src={doctor?.avatar || "https://via.placeholder.com/96"}
                            alt={doctor?.name}
                            className="w-24 h-24 rounded-full object-cover mx-auto sm:mx-0"
                        />

                        <div className="text-center sm:text-left flex-1">
                            <p className="text-primary flex items-center justify-center sm:justify-start text-xs font-semibold uppercase tracking-wider">
                                <Stethoscope size={14} color="#055BD9" className="mr-1.5" />
                                {doctor?.department || "Cardiology"}
                            </p>
                            <h2 className="text-2xl font-bold text-[#1F1E1E] mt-1">
                                {doctor?.name || "Dr. Amit Sharma"}
                            </h2>

                            {/* Status Badge - Visible on mobile only (below name) */}
                            <div className="block sm:hidden mt-3">
                                <p
                                    className={`text-xs font-semibold px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full inline-block ${getStatusColor(
                                        "appointment",
                                        appointment_status
                                    )}`}
                                >
                                    {appointment_status || "N/A"}
                                </p>
                            </div>

                            {/* Experience & Review Cards */}
                            <div className="flex flex-wrap justify-center sm:justify-start gap-3 sm:gap-4 mt-2">

                                {/* Experience Card */}
                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D]">Experience</p>
                                    <p className="text-xs font-semibold text-[#4D4D4D]">
                                        {doctor?.years_experience || "N/A"}
                                    </p>
                                </div>

                                {/* Review Card */}
                                <div className="flex items-center gap-x-1.5 bg-light-gray rounded-xl py-2 px-2.5 h-fit">
                                    <p className="text-xs text-[#4D4D4D] flex items-center gap-x-1">
                                        <Star size={14} color="#FABD2E" fill="#FABD2E" />
                                        Rating
                                    </p>
                                    <div className="text-[#4D4D4D] font-bold">
                                        {doctor?.average_rating || "N/A"}
                                        <span className="text-xs text-gray-400"> ({doctor?.total_reviews || "0"})</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Status Badge - Visible on desktop only (top right) */}
                    <div className="hidden sm:block">
                        <p
                            className={`text-xs font-semibold px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full inline-block ${getStatusColor(
                                "appointment",
                                appointment_status
                            )}`}
                        >
                            {appointment_status || "N/A"}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
