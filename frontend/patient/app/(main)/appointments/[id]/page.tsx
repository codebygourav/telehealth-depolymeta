"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { use } from "react";
import DoctorInfoCard from "@/components/pages/appointment-summary/DoctorInfoCard";
import PatientInfoCard from "@/components/pages/appointment-summary/PatientInfoCard";
import ConfirmButton from "@/components/pages/appointment-summary/ConfirmButton";
import LoadingSkeleton from "@/components/pages/appointment-summary/LoadingSkeleton";
import CustomDialog from "@/components/custom/Dialogboxs";
import { AlertCircle, CheckCircle2, ChevronRight } from "lucide-react";
import { useState } from "react";
import {
  appointmentDetailKeys,
  useAppointmentDetail,
} from "@/queries/useAppointmentSummary";
import type {
  AppointmentDetailData,
  AppointmentPatient,
  AppointmentPayment,
  AppointmentSchedule,
} from "@/types/appointment-summary";
import { useVerifyPayment } from "@/mutations/useVerifyPayment";
import { useQueryClient } from "@tanstack/react-query";
import HeroSection from "@/components/hero-section";
import ScheduleDetails from "@/components/pages/appointment-summary/ScheduleDetails";
import PaymentSummary from "@/components/pages/appointment-summary/PaymentSummary";
import { Button } from "@base-ui/react/button";

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
    date: searchParams.get("date") || "",
    timeSlot: searchParams.get("timeSlot") || "",
    consultationType:
      (searchParams.get("consultationType") as "video" | "in_person") ||
      "in_person",
    patientName: searchParams.get("patientName") || "",
    patientAge: parseInt(searchParams.get("patientAge") || "0"),
    patientGender: searchParams.get("patientGender") || "",
  };
  const [isConfirming, setIsConfirming] = useState(false);
  const [dialogState, setDialogState] = useState<{
    open: boolean;
    type: "danger" | "success";
    title: string;
    description: string;
  }>({
    open: false,
    type: "danger",
    title: "",
    description: "",
  });

  const { data, isLoading, error, refetch } =
    useAppointmentDetail(AppointmentId);
  console.log("appointment data", data?.data);
  const doctor = data?.data;
  const patient = data?.data?.patient;
  const Data: AppointmentDetailData | undefined = data?.data;
  const schedule = data?.data?.schedule;
  const status = data?.data?.status;
  const statusLabel = data?.data?.status_label;

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
      await new Promise((resolve) => setTimeout(resolve, 1500));

      // Do NOT show dialog here! Wait until after payment/verification.
      // Move dialog logic to payment handlers.

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
        name: "Telehealth Deploymeta",
        description: doctor?.doctor?.name,
        order_id: razorpayOrderId,

        handler: async function (response: any) {
          if (
            !response?.razorpay_order_id ||
            !response?.razorpay_payment_id ||
            !response?.razorpay_signature
          )
            return;

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
                  description:
                    "Your appointment is confirmed. You will receive a confirmation email shortly.",
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
            },
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
        type: "danger",
        title: "Booking Failed",
        description: "Unable to confirm appointment. Please try again.",
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
      <div className="container-max-width w-full mx-auto py-12 text-center">
        <div className="py-12 text-center">
          <AlertCircle className="w-12 h-12 mx-auto mb-4 text-destructive" />
          <p className="text-destructive">
            Failed to load appointment details.
          </p>
        </div>
      </div>
    );
  }

  return (
    <>
      <div>
        <HeroSection
          title="Review Appointment"
          description="Please confirm your session details"
        />

        <div className="container-max-width w-full mx-auto grid items-start grid-cols-1 gap-5 lg:grid-cols-12">
          <div className="space-y-8 lg:col-span-7">
            <div className="space-y-8">
              <DoctorInfoCard doctor={doctor.doctor} />
            </div>
            <div className="grid items-start grid-cols-1 gap-5 lg:grid-cols-12">
              <div className="space-y-8 lg:col-span-6">
                <PatientInfoCard patient={patient as AppointmentPatient} />
              </div>
              <div className="space-y-8 lg:col-span-6">
                <ScheduleDetails schedule={schedule as AppointmentSchedule} />
              </div>
            </div>
          </div>

          {/* Right Column: Booking Ticket */}
          <div className="space-y-8 lg:col-span-5">
            <PaymentSummary payment={doctor.payment as AppointmentPayment} />
            {doctor.payment.status !== "paid" && (
              <ConfirmButton
                onClick={handleConfirmBooking}
                isLoading={isConfirming}
              />
            )}
            {doctor.payment.status === "paid" && (
              <Button
                onClick={() => {
                  router.push(
                    `/appointments/manage-appointment/${AppointmentId}`,
                  );
                }}
                className="w-full btn-primary-cta"
              >
                Manage Appointment
                <ChevronRight size={20} />
              </Button>
            )}
          </div>
        </div>
      </div>

      {/* Custom Dialog */}
      <CustomDialog
        open={dialogState.open}
        onClose={() => setDialogState((prev) => ({ ...prev, open: false }))}
        type={dialogState.type}
        title={dialogState.title}
        description={dialogState.description}
        confirmText="OK"
        cancelText="Cancel"
        onConfirm={() => setDialogState((prev) => ({ ...prev, open: false }))}
        icon={
          dialogState.type === "danger" ? (
            <AlertCircle className="w-6 h-6 text-destructive" />
          ) : (
            <CheckCircle2 className="w-6 h-6 text-green-600" />
          )
        }
      />
    </>
  );
};

export default AppointmentSummaryPage;
