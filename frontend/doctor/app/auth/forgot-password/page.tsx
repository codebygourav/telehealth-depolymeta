"use client";
import AuthLayout from "@/components/auth/AuthLayout";
import ResetPasswordForm from "@/components/auth/forgot-password/ResetPasswordForm";
import SendOtpForm from "@/components/auth/forgot-password/SendOtpForm";
import VerifyOtpForm from "@/components/auth/forgot-password/VerifyOtpForm";
import { useState } from "react";

type ForgotPasswordStep = "SEND_OTP" | "VERIFY_OTP" | "RESET_PASSWORD";

const ForgotPasswordPage = () => {
  const [step, setStep] = useState<ForgotPasswordStep>("SEND_OTP");
  const [email, setEmail] = useState("");
  const [resetToken, setResetToken] = useState("");

  const handleSendOtpSuccess = (email: string) => {
    setEmail(email);
    setStep("VERIFY_OTP");
  };

  const handleVerifyOtpSuccess = (token: string) => {
    setResetToken(token);
    setStep("RESET_PASSWORD");
  };

  const handleBackToSendOtp = () => {
    setStep("SEND_OTP");
  };

  const renderContent = () => {
    switch (step) {
      case "SEND_OTP":
        return (
          <div className="flex flex-col gap-6">
            <div className="text-center">
              <h1 className="text-2xl font-bold font-source-sans mb-2">
                Forgot Password?
              </h1>
              <p className="text-sm text-muted-foreground font-source-sans">
                Enter your email address to receive a verification code.
              </p>
            </div>
            <SendOtpForm onSuccess={handleSendOtpSuccess} />
          </div>
        );
      case "VERIFY_OTP":
        return (
          <div className="flex flex-col gap-6">
            <div className="text-center">
              <h1 className="text-2xl font-bold font-source-sans mb-2">
                Verify OTP
              </h1>
              <p className="text-sm text-muted-foreground font-source-sans">
                We've sent a 6-digit verification code to{" "}
                <span className="font-semibold">{email}</span>.
              </p>
            </div>
            <VerifyOtpForm
              email={email}
              onSuccess={handleVerifyOtpSuccess}
              onBack={handleBackToSendOtp}
            />
          </div>
        );
      case "RESET_PASSWORD":
        return (
          <div className="flex flex-col gap-6">
            <div className="text-center">
              <h1 className="text-2xl font-bold font-source-sans mb-2">
                Reset Password
              </h1>
              <p className="text-sm text-muted-foreground font-source-sans">
                Create a new password that you haven't used before.
              </p>
            </div>
            <ResetPasswordForm email={email} resetToken={resetToken} />
          </div>
        );
      default:
        return null;
    }
  };

  return (
    <AuthLayout title="" subtitle="">
      {renderContent()}
    </AuthLayout>
  );
};

export default ForgotPasswordPage;
