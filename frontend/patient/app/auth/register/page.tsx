"use client";

import React, { useState } from "react";
import AuthLayout from "@/components/auth/AuthLayout";
import RegisterStep from "@/components/auth/registration/RegisterStep";
import VerifyOtpStep from "@/components/auth/registration/VerifyOtpStep";
import CompleteProfileStep from "@/components/auth/registration/CompleteProfileStep";
import { useRouter } from "next/navigation";

type Step = "register" | "verify" | "complete";

const RegisterPage = () => {
    const router = useRouter();
    const [currentStep, setCurrentStep] = useState<Step>("register");
    const [email, setEmail] = useState<string>("");

    const handleRegisterSuccess = (submittedEmail: string, isVerified?: boolean) => {
        setEmail(submittedEmail);
        if (isVerified) {
            if (typeof window !== "undefined") {
                localStorage.setItem('reg_email', submittedEmail);
            }
            router.push(`/auth/complete-profile`);
        } else {
            setCurrentStep("verify");
        }
    };

    const handleVerifySuccess = () => {
        setCurrentStep("complete");
    };

    const renderContent = () => {
        switch (currentStep) {
            case "register":
                return (
                    <div className="flex flex-col gap-6">
                        <div className="text-center">
                            <h1 className="text-2xl font-bold font-source-sans mb-2">
                                Create an Account
                            </h1>
                            <p className="text-sm text-muted-foreground font-source-sans">
                                Enter your email address to receive a verification code.
                            </p>
                        </div>
                        <RegisterStep onSuccess={handleRegisterSuccess} />
                    </div>
                );
            case "verify":
                return (
                    <div className="flex flex-col gap-6">
                        <div className="text-center">
                            <h1 className="text-2xl font-bold font-source-sans mb-2">
                                Verify Email
                            </h1>
                            <p className="text-sm text-muted-foreground font-source-sans px-4">
                                We've sent a code to <span className="font-semibold text-foreground">{email}</span>. Please enter it below.
                            </p>
                        </div>
                        <VerifyOtpStep email={email} onSuccess={handleVerifySuccess} />
                    </div>
                );
            case "complete":
                return (
                    <div className="flex flex-col gap-6">
                        <div className="text-center">
                            <h1 className="text-2xl font-bold font-source-sans mb-2">
                                Complete Your Profile
                            </h1>
                            <p className="text-sm text-muted-foreground font-source-sans px-4">
                                Almost there! Please provide additional details to finish your registration.
                            </p>
                        </div>
                        <CompleteProfileStep email={email} />
                    </div>
                );
            default:
                return null;
        }
    };

    return (
        <AuthLayout title="" subtitle="">
            <div className="w-full">
                {renderContent()}
            </div>
        </AuthLayout>
    );
};

export default RegisterPage;
