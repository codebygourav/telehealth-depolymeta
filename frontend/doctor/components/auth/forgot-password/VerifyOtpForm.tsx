"use client";
import React from "react";
import { useForm, FormProvider } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import InputField from "@/components/custom/inputfield";
import { Button } from "@/components/ui/button";
import { useVerifyOtp } from "@/mutations/useForgotPassword";
import { useResendOtp } from "@/mutations/useResendOtp";
import { toast } from "sonner";

const verifyOtpSchema = z.object({
    otp: z.string().length(6, "OTP must be exactly 6 digits"),
});

type VerifyOtpFormData = z.infer<typeof verifyOtpSchema>;

interface VerifyOtpFormProps {
    email: string;
    onSuccess: (resetToken: string) => void;
    onBack?: () => void;
}

const VerifyOtpForm: React.FC<VerifyOtpFormProps> = ({ email, onSuccess, onBack }) => {
    const { mutate, isPending } = useVerifyOtp();
    const { mutate: resendOtp, isPending: isResending } = useResendOtp();

    const methods = useForm<VerifyOtpFormData>({
        resolver: zodResolver(verifyOtpSchema),
        defaultValues: {
            otp: "",
        },
    });

    const onSubmit = async (data: VerifyOtpFormData) => {
        mutate({ email, otp: data.otp }, {
            onSuccess: (response) => {
                toast.success(response.message || "OTP verified successfully");
                const resetToken = response.data?.reset_token;
                if (resetToken) {
                    onSuccess(resetToken);
                } else {
                    toast.error("No reset token returned from API");
                }
            },
            onError: (error: any) => {
                console.error(error);
                const errorMsg = error?.response?.data?.errors?.message;
                if (errorMsg) {
                    methods.setError("otp", { type: "server", message: errorMsg });
                } else {
                    toast.error(error?.response?.data?.message || "Invalid OTP. Please try again.");
                }
            },
        });
    };

    const handleResend = () => {
        resendOtp({ email, context: "forgot_password" }, {
            onSuccess: (response) => {
                toast.success(response.message || "OTP resent successfully to your email.");
                methods.setValue("otp", "");
            },
            onError: (error: any) => {
                console.error(error);
                toast.error(error?.response?.data?.message || "Failed to resend OTP. Please try again.");
            }
        });
    };

    return (
        <FormProvider {...methods}>
            <form onSubmit={methods.handleSubmit(onSubmit)} className="space-y-4">
                <div className="space-y-1">
                    <InputField
                        name="otp"
                        label="Verification Code (OTP)"
                        placeholder="Enter 6-digit OTP"
                        required
                    />
                    <div className="flex justify-end pt-1">
                        <button
                            type="button"
                            onClick={handleResend}
                            disabled={isResending || isPending}
                            className="text-xs text-primary font-medium hover:underline disabled:opacity-50"
                        >
                            {isResending ? "Sending OTP..." : "Resend OTP"}
                        </button>
                    </div>
                </div>
                <Button type="submit" className="w-full" disabled={isPending}>
                    {isPending ? "Verifying..." : "Verify OTP"}
                </Button>
                {onBack && (
                    <Button type="button" variant="ghost" className="w-full" onClick={onBack} disabled={isPending}>
                        Back to change email
                    </Button>
                )}
            </form>
        </FormProvider>
    );
};

export default VerifyOtpForm;
