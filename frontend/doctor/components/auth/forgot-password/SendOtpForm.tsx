"use client";
import InputField from "@/components/custom/inputfield";
import { Button } from "@/components/ui/button";
import { useSendOtp } from "@/mutations/useForgotPassword";
import { zodResolver } from "@hookform/resolvers/zod";
import React from "react";
import { FormProvider, useForm } from "react-hook-form";
import { toast } from "sonner";
import * as z from "zod";

const sendOtpSchema = z.object({
    email: z.string().email("Please enter a valid email address"),
});

type SendOtpFormData = z.infer<typeof sendOtpSchema>;

interface SendOtpFormProps {
    onSuccess: (email: string) => void;
}

const SendOtpForm: React.FC<SendOtpFormProps> = ({ onSuccess }) => {
    const { mutate, isPending } = useSendOtp();

    const methods = useForm<SendOtpFormData>({
        resolver: zodResolver(sendOtpSchema),
        defaultValues: {
            email: "",
        },
    });

    const onSubmit = async (data: SendOtpFormData) => {
        mutate(data, {
            onSuccess: () => {
                toast.success("OTP sent to your email");
                onSuccess(data.email);
            },
            onError: (error: any) => {
                console.error(error);
                const errorMsg = error?.response?.data?.errors?.message;
                if (errorMsg) {
                    methods.setError("email", { type: "server", message: errorMsg });
                } else {
                    toast.error(
                        error?.response?.data?.message ||
                        "Something went wrong. Please try again.",
                    );
                }
            },
        });
    };

    return (
        <FormProvider {...methods}>
            <form onSubmit={methods.handleSubmit(onSubmit)} className="space-y-4">
                <InputField
                    name="email"
                    label="Email Address"
                    placeholder="Enter your email"
                    required
                />
                <Button type="submit" className="w-full" disabled={isPending}>
                    {isPending ? "Sending..." : "Send OTP"}
                </Button>
                <Button
                    asChild
                    type="button"
                    variant="ghost"
                    className="w-full"
                    disabled={isPending}
                >
                    <a href="/auth/login">Back to Login</a>
                </Button>
            </form>
        </FormProvider>
    );
};

export default SendOtpForm;
