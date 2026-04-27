"use client";

import React, { useState } from 'react';
import { use } from 'react';
import { useRouter } from 'next/navigation';
import { useDoctorDetail } from '@/queries/useDoctorDetail';
import DoctorHeader from '@/components/pages/doctor-profile/DoctorHeader';
import DoctorStats from '@/components/pages/doctor-profile/DoctorStats';
import DoctorAbout from '@/components/pages/doctor-profile/DoctorAbout';
import DoctorEducation from '@/components/pages/doctor-profile/DoctorEducation';
import AppointmentBooking from '@/components/pages/doctor-profile/AppointmentBooking';
import ReviewSection from '@/components/pages/doctor-profile/ReviewSection';
import LoadingSkeleton from '@/components/pages/doctor-profile/LoadingSkeleton';
import CustomDialog from '@/components/custom/Dialogboxs';
import CustomTabs from '@/components/custom/CustomTabs';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface DoctorProfileProps {
    params: Promise<{
        id: string;
    }>;
}

const DoctorProfile = ({ params }: DoctorProfileProps) => {

    const { id } = use(params);
    const router = useRouter();
    const [activeTab, setActiveTab] = useState<'overview' | 'reviews'>('overview');
    const [appointmentId, setAppointmentId] = useState<string>('');
    const [dialogState, setDialogState] = useState<{
        open: boolean;
        type: 'danger' | 'success';
        title: string;
        description: string;
    }>({
        open: false,
        type: 'danger',
        title: '',
        description: '',
    });

    const { data, error, isLoading, refetch } = useDoctorDetail(id);

    console.log("schdule data" ,data);
    
    const doctor = data?.data;
    console.log("doctor data", data);


    const handleBookingSuccess = (appointmentId: string) => {
        setDialogState({
            open: true,
            type: 'success',
            title: 'Appointment Booked!',
            description: 'Your appointment has been successfully scheduled. Check your email for details.',
        });
        setAppointmentId(appointmentId);
    };

    const handleBookingError = (error: string) => {
        setDialogState({
            open: true,
            type: 'danger',
            title: 'Booking Failed',
            description: error || 'Unable to book appointment. Please try again later.',
        });
    };

    if (isLoading) {
        return <LoadingSkeleton />;
    }

    if (error || !doctor) {
        return (
            <div className="text-center py-12">
                <AlertCircle className="mx-auto h-12 w-12 text-destructive mb-4" />
                <p className="text-destructive mb-4">Failed to load doctor details. Please try again.</p>
                <Button onClick={() => refetch()} variant="default">
                    Retry
                </Button>
            </div>
        );
    }

    return (
        <>
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                {/* Left Column - Doctor Info */}
                <div className="lg:col-span-8 space-y-8">
                    <DoctorHeader doctor={doctor} />

                    <div className="space-y-6">
                        {/* Tabs Container - Centered on all devices */}
                        <div className="flex justify-center md:justify-start">
                            <div className="flex gap-4 sm:gap-8 border-b border-outline-variant/20 overflow-x-auto no-scrollbar px-2">
                                <button
                                    onClick={() => setActiveTab('overview')}
                                    className={`
                                                pb-3 sm:pb-4 
                                                transition-all 
                                                whitespace-nowrap
                                                text-sm sm:text-base
                                                ${activeTab === 'overview'
                                            ? 'text-primary font-bold border-b-2 border-primary'
                                            : 'text-on-surface-variant font-medium hover:text-primary'
                                        }
                                     `}
                                >
                                    Overview
                                </button>
                                <button
                                    onClick={() => setActiveTab('reviews')}
                                    className={`
                                                pb-3 sm:pb-4 
                                                transition-all 
                                                whitespace-nowrap
                                                text-sm sm:text-base
                                                ${activeTab === 'reviews'
                                            ? 'text-primary font-bold border-b-2 border-primary'
                                            : 'text-on-surface-variant font-medium hover:text-primary'
                                        }
                                `}
                                >
                                    Reviews ({doctor.review_summary?.total_reviews || 0})
                                </button>
                            </div>
                        </div>

                        {/* Content Sections */}
                        {activeTab === 'overview' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <DoctorAbout about={doctor.about} className="md:col-span-2" />
                                <DoctorStats
                                    patientsHelped={5000}
                                    experience={doctor.profile.years_experience}
                                />
                                <DoctorEducation education={doctor.education} />
                            </div>
                        )}

                        {activeTab === 'reviews' && (
                            <ReviewSection reviews={doctor.doctor_reviews || []} />
                        )}
                    </div>
                </div>

                {/* Right Column - Booking */}
                <div className="lg:col-span-4 sticky top-28">
                    <AppointmentBooking
                        doctor={doctor}
                        onBookingSuccess={handleBookingSuccess}
                        onBookingError={handleBookingError}
                    />
                </div>
            </div>

            {/* Custom Dialog */}
            <CustomDialog
                open={dialogState.open}
                onClose={() => setDialogState(prev => ({ ...prev, open: false }))}
                type={dialogState.type}
                title={dialogState.title}
                description={dialogState.description}
                confirmText="OK"
                cancelText="Cancel"
                onConfirm={
                    () => {
                        setDialogState(prev => ({ ...prev, open: false }))
                        router.push(`/appointments/${appointmentId}`);
                    }
                }
                icon={dialogState.type === 'danger' ?
                    <AlertCircle className="h-6 w-6 text-destructive" /> :
                    <CheckCircle2 className="h-6 w-6 text-green-600" />
                }
            />
        </>
    );
};

export default DoctorProfile;