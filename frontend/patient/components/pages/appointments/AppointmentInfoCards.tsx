"use client";

import { Card, CardContent } from "@/components/ui/card";
import { Star, Stethoscope, Calendar, User, CreditCard, FileText, Eye, Download } from "lucide-react";
import { getStatusColor } from "@/src/utils/getStatusColor";

interface AppointmentInfoCardsProps {
    data: any;
}

export const AppointmentInfoCards = ({ data }: AppointmentInfoCardsProps) => {

    const { doctor, schedule, patient, payment, medical_reports } = data;

    return (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4  sm:gap-5 md:gap-6">

            {/* LEFT COLUMN - Main Info */}
            <div className="lg:col-span-2 space-y-4 sm:space-y-5 md:space-y-6">

                {/* Doctor Card */}
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
                                                data.status_label
                                            )}`}
                                        >
                                            {data.status_label || "N/A"}
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
                                        data.status_label
                                    )}`}
                                >
                                    {data.status_label || "N/A"}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Schedule & Patient Info Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 md:gap-6">

                    {/* Schedule Card */}
                    <Card className="rounded-lg p-5">
                        <CardContent className="p-0 space-y-5">
                            <h3 className="text-lg text-[#1F1E1E] font-semibold flex items-center gap-2.5">
                                <Calendar size={18} color='#055BD9' />
                                Schedule Detail
                            </h3>
                            <div className="space-y-2.5 sm:space-y-3">
                                <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                    <span className="text-xs sm:text-sm text-[#4D4D4D]">Date</span>
                                    <span className="text-xs sm:text-sm font-medium text-[#4D4D4D]">
                                        {schedule?.date_formatted || "N/A"}
                                    </span>
                                </div>
                                <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                    <span className="text-xs sm:text-sm text-[#4D4D4D]">Time</span>
                                    <span className="text-xs sm:text-sm font-medium text-[#4D4D4D]">
                                        {schedule?.time_formatted || "N/A"}
                                    </span>
                                </div>
                                <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                    <span className="text-xs sm:text-sm text-[#4D4D4D]">Consultation Type</span>
                                    <span
                                        className={`text-[10px] sm:text-xs px-2 py-1 rounded-full w-fit ${getStatusColor(
                                            "session",
                                            schedule?.consultation_type_label
                                        )}`}
                                    >
                                        {schedule?.consultation_type_label || "N/A"}
                                    </span>
                                </div>
                                <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                    <span className="text-xs sm:text-sm text-[#4D4D4D]">Booking Type</span>
                                    <span className="text-xs sm:text-sm font-medium text-[#4D4D4D]">
                                        {schedule?.booking_type || "N/A"}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Patient Info Card */}
                    <Card className="rounded-lg p-5">
                        <CardContent className="p-0 space-y-3 sm:space-y-4">
                            <h3 className="text-lg text-[#1F1E1E] font-semibold flex items-center gap-2.5">
                                <User size={18} color='#055BD9' />
                                Patient Info
                            </h3>
                            <div className="space-y-2.5 sm:space-y-3">
                                <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                    <span className="text-xs sm:text-sm text-[#4D4D4D]">Name</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                        {patient?.name || "N/A"}
                                    </span>
                                </div>
                                <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                    <span className="text-xs sm:text-sm text-[#4D4D4D]">Age</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                        {patient?.age_formatted || "N/A"}
                                    </span>
                                </div>
                                <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                    <span className="text-xs sm:text-sm text-[#4D4D4D]">Gender</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                        {patient?.gender_formatted || "N/A"}
                                    </span>
                                </div>
                                {patient?.blood_group && (
                                    <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                        <span className="text-xs sm:text-sm text-[#4D4D4D]">Blood Group</span>
                                        <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">{patient.blood_group}</span>
                                    </div>
                                )}
                                <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                    <span className="text-xs sm:text-sm text-[#4D4D4D]">Phone</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">{patient?.phone || "N/A"}</span>
                                </div>
                                <div className="flex flex-row justify-between items-center gap-1 sm:gap-2">
                                    <span className="text-xs sm:text-sm text-[#4D4D4D]">Email</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white break-all text-left sm:text-right">{patient?.email || "N/A"}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Medical Reports Section */}
                {medical_reports?.length > 0 && (
                    <div className="space-y-3 sm:space-y-4 pt-2 sm:pt-4">
                        <h3 className="font-semibold text-gray-900 dark:text-white flex items-center gap-2 px-1 text-base sm:text-lg">
                            <FileText size={20} color="#055BD9" />
                            Medical Reports
                        </h3>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                            {medical_reports.map((report: any) => (
                                <div
                                    key={report.id}
                                    className="flex items-center justify-between p-3 sm:p-4 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors border border-gray-100 dark:border-gray-700"
                                >
                                    <div className="flex items-center gap-3 flex-1 min-w-0">
                                        <div className="w-8 h-8 sm:w-10 sm:h-10 bg-[#055bd929] rounded-lg flex items-center justify-center shrink-0">
                                            <Download size={20} color="#055BD9" />
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className="font-medium text-gray-900 dark:text-white text-sm sm:text-base truncate">
                                                {report.title}
                                            </p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {report.type_label} • {report.report_date_formatted}
                                            </p>
                                        </div>
                                    </div>
                                    <a
                                        href={report.file_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-emerald-600 hover:text-emerald-700 transition-colors shrink-0 ml-2"
                                    >
                                        <Eye size={18} color="#055BD9" />
                                    </a>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* RIGHT COLUMN - Payment Card */}
            <div className="lg:col-span-1">
                <div className="sticky top-24">
                    <Card className="rounded-lg p-5">
                        <CardContent className="p-0 space-y-4 sm:space-y-5 md:space-y-6">

                            <h3 className="text-lg text-[#1F1E1E] font-semibold flex items-center gap-2.5">
                                <span className="p-1.5 bg-[#055bd929] rounded-md">
                                    <CreditCard size={18} color='#055BD9' />
                                </span>
                                Payment Detail
                            </h3>

                            <div className="space-y-3 sm:space-y-4">
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-sm text-[#4D4D4D]">Consultation Fee</span>
                                    <span className="text-sm text-[#4D4D4D]">
                                        {payment?.consultation_fee_formatted || "₹0.00"}
                                    </span>
                                </div>
                                {payment?.admin_fee && (
                                    <div className="flex justify-between items-center flex-wrap gap-2">
                                        <span className="text-sm text-[#4D4D4D]">Admin Fee</span>
                                        <span className="text-sm text-[#4D4D4D] font-bold">
                                            {payment?.admin_fee_formatted || "₹0.00"}
                                        </span>
                                    </div>
                                )}
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-sm text-[#4D4D4D]">Additional Discount</span>
                                    <span className="text-sm text-[#4D4D4D] font-bold">
                                        {payment?.discount_formatted || "₹0.00"}
                                    </span>
                                </div>

                                <div className="pt-4 sm:pt-6 relative">
                                    <div className="border-t border-dashed border-gray-200 dark:border-gray-700 pt-4 sm:pt-6">
                                        <div className="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-3">
                                            <div>
                                                <p className="text-xs font-semibold uppercase text-[#4D4D4D]">
                                                    TOTAL PAID
                                                </p>
                                                <div className="flex items-center gap-1">
                                                    <span className="text-3xl text-[#1F1E1E] font-bold">
                                                        {payment?.total_formatted?.includes('₹') ? '₹' : ''}
                                                        {payment?.total_formatted?.replace(/[^\d.]/g, '') || "0"}
                                                    </span>
                                                </div>
                                            </div>
                                            <span
                                                className={`px-2.5 sm:px-3 py-1 rounded-md text-[10px] sm:text-xs font-bold tracking-wider self-start sm:self-auto ${getStatusColor(
                                                    "payment",
                                                    payment?.status
                                                )}`}
                                            >
                                                {payment?.status_label || "N/A"}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {payment?.payment_method && (
                                    <div className="flex justify-between items-center pt-3 sm:pt-4 border-t border-gray-50 dark:border-gray-800">
                                        <span className="text-sm text-[#1F1E1E]">Payment Method</span>
                                        <span className="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                            {payment.payment_method}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
};