"use client";

import { useRouter, useSearchParams } from 'next/navigation';
import { use } from 'react';
import SummaryHeader from '@/components/pages/appointment-summary/SummaryHeader';
import DoctorInfoCard from '@/components/pages/appointment-summary/DoctorInfoCard';
import PatientInfoCard from '@/components/pages/appointment-summary/PatientInfoCard';
import ConsultationModeCard from '@/components/pages/appointment-summary/ConsultationModeCard';
import BookingTicket from '@/components/pages/appointment-summary/BookingTicket';
import ConfirmButton from '@/components/pages/appointment-summary/ConfirmButton';
import LoadingSkeleton from '@/components/pages/appointment-summary/LoadingSkeleton';
import CustomDialog from '@/components/custom/Dialogboxs';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
import { appointmentDetailKeys, useAppointmentDetail } from '@/queries/useAppointmentSummary';
import type { AppointmentDetailData } from '@/types/appointment-summary';
import { useVerifyPayment } from '@/mutations/useVerifyPayment';
import { useQueryClient } from '@tanstack/react-query';

interface PageProps {
    params: Promise<{
        id: string;
    }>;
}

const AppointmentSummaryPage = ({ params }: PageProps) => {

    const { id: AppointmentId } = use(params);
    const router = useRouter();
    const searchParams = useSearchParams();

    // Get appointment data from URL search params
    const appointmentData = {
        date: searchParams.get('date') || '',
        timeSlot: searchParams.get('timeSlot') || '',
        consultationType: (searchParams.get('consultationType') as 'video' | 'in_person') || 'in_person',
        patientName: searchParams.get('patientName') || '',
        patientAge: parseInt(searchParams.get('patientAge') || '0'),
        patientGender: searchParams.get('patientGender') || '',
    };
    const [isConfirming, setIsConfirming] = useState(false);
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

    const { data, isLoading, error, refetch } = useAppointmentDetail(AppointmentId);
    
    const doctor = data?.data;
    const patient = data?.data?.patient;
    const Data: AppointmentDetailData | undefined = data?.data;
    const schedule = data?.data?.schedule;



    const queryClient = useQueryClient();
    const { mutate: verifyPayment } = useVerifyPayment();
    

    const loadRazorpayScript = () => {
        return new Promise((resolve) => {
            if ((window as any).Razorpay) return resolve(true);

            const script = document.createElement("script");
            script.src = "https://checkout.razorpay.com/v1/checkout.js";
            script.onload = () => resolve(true);
            script.onerror = () => resolve(false);

            document.body.appendChild(script);
        });
    };


    const handleConfirmBooking = async () => {
        setIsConfirming(true);
        try {
            // API call to confirm booking
            await new Promise(resolve => setTimeout(resolve, 1500));

            setDialogState({
                open: true,
                type: 'success',
                title: 'Appointment Confirmed!',
                description: 'Your appointment has been successfully booked. You will receive a confirmation email shortly.',
            });

            const res = await loadRazorpayScript();

            if (!res) {
                alert("Razorpay SDK failed to load");
                return;
            }

            // Validate fields
            if (!doctor?.razorpay_key_id || !doctor?.razorpay_order_id) {
                alert("Payment info missing");
                return;
            }

            const razorpayKeyId = doctor.razorpay_key_id;
            const razorpayOrderId = doctor.razorpay_order_id;

            const options = {
                key: razorpayKeyId,
                amount: doctor.payment.total, // already in paise
                currency: "INR",
                name: "CMC Telehealth",
                description: doctor?.doctor?.name,
                order_id: razorpayOrderId,

                handler: async function (response: any) {

                    if (
                        !response?.razorpay_order_id ||
                        !response?.razorpay_payment_id ||
                        !response?.razorpay_signature
                    ) return;

                    verifyPayment(
                        {
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            appointment_id: doctor?.appointment_id,
                            razorpay_signature: response.razorpay_signature,
                        },
                        {
                            onSuccess: (res) => {
                                queryClient.invalidateQueries({
                                    queryKey: appointmentDetailKeys.detail(AppointmentId),
                                });

                                refetch();

                                setDialogState({
                                    open: true,
                                    type: "success",
                                    title: "Payment Successful",
                                    description: "Your appointment is confirmed",
                                });
                            },
                            onError: () => {
                                setDialogState({
                                    open: true,
                                    type: "danger",
                                    title: "Verification Failed",
                                    description: "Payment done but verification failed",
                                });
                            },
                        }
                    );
                },

                prefill: {
                    name: patient?.name,
                    email: patient?.email,
                    contact: patient?.phone,
                },

                theme: {
                    color: "#013220",
                },
            };

            const rzp = new (window as any).Razorpay(options);

            rzp.on("payment.failed", function (response: any) {
                setDialogState({
                    open: true,
                    type: "danger",
                    title: "Payment Failed",
                    description: response.error.description,
                });
            });

            rzp.open();

        } catch (error) {
            setDialogState({
                open: true,
                type: 'danger',
                title: 'Booking Failed',
                description: 'Unable to confirm appointment. Please try again.',
            });
        } finally {
            setIsConfirming(false);
        }
    };

    if (isLoading) {
        return <LoadingSkeleton />;
    }

    if (error || !doctor) {
        return (
            <div className="max-w-4xl mx-auto pb-12">
                <div className="text-center py-12">
                    <AlertCircle className="mx-auto h-12 w-12 text-destructive mb-4" />
                    <p className="text-destructive">Failed to load appointment details.</p>
                </div>
            </div>
        );
    }

    return (
        <>
            <div className="max-w-4xl mx-auto pb-12">
                <SummaryHeader />

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">

                    {/* Left Column: Doctor & Patient Info */}
                    <div className="lg:col-span-7 space-y-8">
                        <div className="space-y-8">
                            <DoctorInfoCard doctor={doctor.doctor} />
                            <PatientInfoCard
                                name={patient?.name || ''}
                                age={patient?.age || 0}
                                gender={patient?.gender || ''}
                            />
                        </div>

                        <ConsultationModeCard type={Data?.schedule?.consultation_type as 'video' | 'in_person' || 'video'} />
                    </div>

                    {/* Right Column: Booking Ticket */}
                    <div className="lg:col-span-5 space-y-8">
                        <BookingTicket
                            schedule={schedule}
                            doctor={doctor.doctor}
                            payment={doctor.payment}
                            date={appointmentData.date}
                            timeSlot={appointmentData.timeSlot}
                            consultationType={appointmentData.consultationType}
                        />

                        <ConfirmButton
                            onClick={handleConfirmBooking}
                            isLoading={isConfirming}
                        />
                    </div>
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
                onConfirm={() => setDialogState(prev => ({ ...prev, open: false }))}
                icon={dialogState.type === 'danger' ?
                    <AlertCircle className="h-6 w-6 text-destructive" /> :
                    <CheckCircle2 className="h-6 w-6 text-green-600" />
                }
            />
        </>
    );
};

export default AppointmentSummaryPage;