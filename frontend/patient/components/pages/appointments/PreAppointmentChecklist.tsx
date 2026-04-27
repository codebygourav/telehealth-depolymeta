'use client';
import { FileText, Pill } from 'lucide-react';
import Link from 'next/link';

const PreAppointmentChecklist = () => {
    return (
        <div className="bg-[#0A2E1F] rounded-2xl sm:rounded-3xl md:rounded-[32px] p-5 sm:p-6 md:p-8 text-white relative overflow-hidden mt-6 sm:mt-8">
            {/* Background Blur Effect */}
            <div className="absolute top-0 right-0 w-48 h-48 sm:w-56 sm:h-56 md:w-64 md:h-64 bg-white/5 rounded-full -mr-16 -mt-16 sm:-mr-20 sm:-mt-20 blur-2xl sm:blur-3xl"></div>

            <div className="relative z-10 flex flex-col lg:flex-row justify-between gap-6 sm:gap-8">
                {/* Left Section - Text Content */}
                <div className="max-w-xl flex-1">
                    <h2 className="text-xl sm:text-2xl md:text-3xl font-bold mb-2 sm:mb-3 font-headline">
                        Pre-appointment Checklist
                    </h2>
                    <p className="text-white/70 text-xs sm:text-sm md:text-base mb-4 sm:mb-5 md:mb-6 leading-relaxed">
                        Ensure you have your latest medical history and current medications list ready before your session.
                    </p>
                    <button className="w-full sm:w-auto px-4 sm:px-5 md:px-6 py-2.5 sm:py-3 bg-white text-[#0A2E1F] rounded-xl font-bold text-xs sm:text-sm shadow-lg hover:bg-gray-100 transition-all duration-300">
                        View Preparation Guide
                    </button>
                </div>

                {/* Right Section - Action Cards */}
                <div className="flex flex-row sm:flex-row justify-center sm:justify-end gap-3 sm:gap-4">
                    {/* Medical Records Card */}
                    <Link href="/medical-records">
                        <div className="group bg-white/10 p-3 sm:p-4 rounded-2xl sm:rounded-[24px] flex flex-col items-center justify-center gap-2 w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 border border-white/10 hover:bg-white/20 transition-all duration-300 cursor-pointer">
                            <div className="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 bg-white/10 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <FileText className="w-4 h-4 sm:w-4.5 sm:h-4.5 md:w-5 md:h-5 text-white" />
                            </div>
                            <span className="text-[8px] sm:text-[9px] md:text-[10px] font-bold uppercase tracking-wider text-center leading-tight">
                                Medical Records
                            </span>
                        </div>
                    </Link>

                    {/* Medications List Card */}
                    <Link href="/my-medicines">
                        <div className="group bg-white/10 p-3 sm:p-4 rounded-2xl sm:rounded-[24px] flex flex-col items-center justify-center gap-2 w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 border border-white/10 hover:bg-white/20 transition-all duration-300 cursor-pointer">
                            <div className="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 bg-white/10 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <Pill className="w-4 h-4 sm:w-4.5 sm:h-4.5 md:w-5 md:h-5 text-white" />
                            </div>
                            <span className="text-[8px] sm:text-[9px] md:text-[10px] font-bold uppercase tracking-wider text-center leading-tight">
                                Meds List
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default PreAppointmentChecklist;