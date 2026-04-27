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
                <Card className="bg-white dark:bg-gray-900 rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 shadow-sm border border-gray-100 dark:border-gray-800">
                    <CardContent className="p-0">
                        <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                            <div className="flex flex-col sm:flex-row gap-4 sm:gap-5 flex-1">
                                <img
                                    src={doctor?.avatar || "https://via.placeholder.com/96"}
                                    alt={doctor?.name}
                                    className="w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 lg:w-32 lg:h-32 rounded-xl sm:rounded-2xl object-cover mx-auto sm:mx-0"
                                />

                                <div className="text-center sm:text-left flex-1">
                                    <p className="text-emerald-600 dark:text-emerald-500 flex items-center justify-center sm:justify-start text-xs font-semibold uppercase tracking-wider">
                                        <Stethoscope className="w-3.5 h-3.5 sm:w-4 sm:h-4 mr-1.5" />
                                        {doctor?.department || "Cardiology"}
                                    </p>
                                    <h2 className="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mt-1">
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
                                    <div className="flex flex-wrap justify-center sm:justify-start gap-3 sm:gap-4 mt-3 sm:mt-4">
                                        {/* Experience Card */}
                                        <div className="bg-gray-100 dark:bg-gray-800 rounded-xl p-2.5 sm:p-3 min-w-[100px] sm:min-w-[110px]">
                                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-0.5 sm:mb-1">Experience</p>
                                            <p className="font-semibold text-gray-900 dark:text-white text-base sm:text-lg">
                                                {doctor?.years_experience || "N/A"}
                                            </p>
                                        </div>

                                        {/* Review Card */}
                                        <div className="bg-gray-100 dark:bg-gray-800 rounded-xl p-2.5 sm:p-3 min-w-[100px] sm:min-w-[110px]">
                                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-0.5 sm:mb-1 flex items-center gap-1 sm:gap-2">
                                                <Star className="w-3.5 h-3.5 sm:w-4 sm:h-4 fill-yellow-400 text-yellow-400" />
                                                Rating
                                            </p>
                                            <div className="flex items-center gap-1">
                                                <div className="font-semibold text-gray-900 dark:text-white text-base sm:text-lg">
                                                    {doctor?.average_rating || "N/A"}
                                                    <span className="text-xs text-gray-400"> ({doctor?.total_reviews || "0"})</span>
                                                </div>
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
                    <Card className="bg-white dark:bg-gray-900 rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 shadow-sm border border-gray-100 dark:border-gray-800">
                        <CardContent className="p-0 space-y-3 sm:space-y-4">
                            <h3 className="font-semibold text-gray-900 dark:text-white mb-3 sm:mb-4 flex items-center gap-2 text-base sm:text-lg">
                                <Calendar className="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600" />
                                Schedule
                            </h3>
                            <div className="space-y-2.5 sm:space-y-3">
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Date</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                        {schedule?.date_formatted || "N/A"}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Time</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                        {schedule?.time_formatted || "N/A"}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Consultation Type</span>
                                    <span
                                        className={`text-[10px] sm:text-xs px-2 py-1 rounded-full ${getStatusColor(
                                            "session",
                                            schedule?.consultation_type_label
                                        )}`}
                                    >
                                        {schedule?.consultation_type_label || "N/A"}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Booking Type</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                        {schedule?.booking_type || "N/A"}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Patient Info Card */}
                    <Card className="bg-white dark:bg-gray-900 rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 shadow-sm border border-gray-100 dark:border-gray-800">
                        <CardContent className="p-0 space-y-3 sm:space-y-4">
                            <h3 className="font-semibold text-gray-900 dark:text-white mb-3 sm:mb-4 flex items-center gap-2 text-base sm:text-lg">
                                <User className="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600" />
                                Patient Info
                            </h3>
                            <div className="space-y-2.5 sm:space-y-3">
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Name</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                        {patient?.name || "N/A"}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Age</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                        {patient?.age_formatted || "N/A"}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Gender</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">
                                        {patient?.gender_formatted || "N/A"}
                                    </span>
                                </div>
                                {patient?.blood_group && (
                                    <div className="flex justify-between items-center flex-wrap gap-2">
                                        <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Blood Group</span>
                                        <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">{patient.blood_group}</span>
                                    </div>
                                )}
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Phone</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white">{patient?.phone || "N/A"}</span>
                                </div>
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Email</span>
                                    <span className="text-xs sm:text-sm font-medium text-gray-900 dark:text-white break-all text-right">{patient?.email || "N/A"}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Medical Reports Section */}
                {medical_reports?.length > 0 && (
                    <div className="space-y-3 sm:space-y-4 pt-2 sm:pt-4">
                        <h3 className="font-semibold text-gray-900 dark:text-white flex items-center gap-2 px-1 text-base sm:text-lg">
                            <FileText className="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600" />
                            Medical Reports
                        </h3>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                            {medical_reports.map((report: any) => (
                                <div
                                    key={report.id}
                                    className="flex items-center justify-between p-3 sm:p-4 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors border border-gray-100 dark:border-gray-700"
                                >
                                    <div className="flex items-center gap-3 flex-1 min-w-0">
                                        <div className="w-8 h-8 sm:w-10 sm:h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center shrink-0">
                                            <Download className="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600" />
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
                                        <Eye className="w-4 h-4 sm:w-5 sm:h-5" />
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
                    <Card className="bg-white dark:bg-gray-900 rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden relative">
                        <CardContent className="p-0 space-y-4 sm:space-y-5 md:space-y-6">
                            <h3 className="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2 text-base sm:text-lg">
                                <span className="p-1.5 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg text-emerald-600">
                                    <CreditCard className="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                </span>
                                Payment Detail
                            </h3>

                            <div className="space-y-3 sm:space-y-4">
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Consultation Fee</span>
                                    <span className="text-sm sm:text-base font-bold text-gray-900 dark:text-white">
                                        {payment?.consultation_fee_formatted || "₹0.00"}
                                    </span>
                                </div>
                                {payment?.admin_fee && (
                                    <div className="flex justify-between items-center flex-wrap gap-2">
                                        <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Admin Fee</span>
                                        <span className="text-sm sm:text-base font-bold text-gray-900 dark:text-white">
                                            {payment?.admin_fee_formatted || "₹0.00"}
                                        </span>
                                    </div>
                                )}
                                <div className="flex justify-between items-center flex-wrap gap-2">
                                    <span className="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Additional Discount</span>
                                    <span className="text-sm sm:text-base font-bold text-gray-900 dark:text-white">
                                        {payment?.discount_formatted || "₹0.00"}
                                    </span>
                                </div>

                                <div className="pt-4 sm:pt-6 relative">
                                    <div className="border-t border-dashed border-gray-200 dark:border-gray-700 pt-4 sm:pt-6">
                                        <div className="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-3">
                                            <div>
                                                <p className="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1 sm:mb-2">
                                                    TOTAL PAID
                                                </p>
                                                <div className="flex items-center gap-1">
                                                    <span className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                                                        {payment?.total_formatted?.includes('₹') ? '₹' : ''}
                                                        {payment?.total_formatted?.replace(/[^\d.]/g, '') || "0"}
                                                    </span>
                                                </div>
                                            </div>
                                            <span
                                                className={`px-2.5 sm:px-3 py-1 rounded-md text-[10px] sm:text-xs font-bold uppercase tracking-wider self-start sm:self-auto ${getStatusColor(
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
                                        <span className="text-xs text-gray-400 dark:text-gray-500">Payment Method</span>
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