"use client";

import React, { useState, useEffect } from "react";
import { FormProvider, useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import InputField from "../../custom/inputfield";
import { useVerifyEmail, VerifyOtpResponse } from "@/mutations/auth/useAuthMutations";
import { useResendOtp } from "@/mutations/auth/useResendOtp";
import { toast } from "sonner"; // Assuming sonner, or user can replace

const otpSchema = z.object({
  otp: z.string().length(6, "OTP must be exactly 6 characters"),
});

type OtpValues = z.infer<typeof otpSchema>;

interface VerifyOtpStepProps {
  email: string;
  onSuccess: () => void;
}

const VerifyOtpStep: React.FC<VerifyOtpStepProps> = ({ email, onSuccess }) => {
  const { mutate: verifyOtp, isPending: isVerifying } = useVerifyEmail();
  const { mutate: resendOtp, isPending: isResending } = useResendOtp();
  const [timer, setTimer] = useState(60);

  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (timer > 0) {
      interval = setInterval(() => setTimer((t) => t - 1), 1000);
    }
    return () => clearInterval(interval);
  }, [timer]);

  const methods = useForm<OtpValues>({
    resolver: zodResolver(otpSchema),
    defaultValues: { otp: "" },
  });

  const onSubmit = (data: OtpValues) => {
    verifyOtp(
      { email, otp: data.otp, context: "registration" },
      {
        onSuccess: (response: VerifyOtpResponse) => {
          if (response.success) {
            toast.success(response.message || "Email verified successfully!");
            onSuccess();
          } else {
            toast.error(response?.errors?.message || response.message || "Invalid OTP code.");
          }
        },
        onError: (err: any) => {
          toast.error(err?.response?.data?.errors?.message || err?.response?.data?.message || err?.message || "Verification failed");
        },
      },
    );
  };

  const handleResend = () => {
    if (timer > 0) return;
    resendOtp(
      { email, context: "registration" },
      {
        onSuccess: (response: any) => {
          if (response.success) {
            toast.success(response.message || "OTP resent successfully!");
            setTimer(60);
          } else {
            toast.error(response?.errors?.message || response.message || "Failed to resend OTP.");
          }
        },
        onError: (err: any) => {
          toast.error(err?.response?.data?.errors?.message || err?.response?.data?.message || err?.message || "Resend failed");
        },
      },
    );
  };

  return (
    <div className="space-y-6">
      <FormProvider {...methods}>
        <form onSubmit={methods.handleSubmit(onSubmit)} className="space-y-5">
          <InputField
            name="otp"
            label="Verification Code"
            placeholder="123456"
            required
            disabled={isVerifying}
            type="text"
            className="text-center font-bold tracking-widest"
          />

          <button
            type="submit"
            disabled={isVerifying}
            className="w-full bg-primary text-primary-foreground hover:bg-primary/90 font-medium py-2.5 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed font-source-sans"
          >
            {isVerifying ? "Verifying..." : "Verify Code"}
          </button>
        </form>
      </FormProvider>

      <div className="text-center">
        <button
          onClick={handleResend}
          disabled={timer > 0 || isResending}
          className={`text-sm ${timer > 0 ? "text-muted-foreground cursor-not-allowed" : "text-primary hover:text-primary/80"
            } transition-colors font-medium`}
        >
          {isResending ? "Sending..." : timer > 0 ? `Resend code in ${timer}s` : "Didn't receive code? Resend"}
        </button>
      </div>
    </div>
  );
};

export default VerifyOtpStep;
