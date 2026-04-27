"use client";

import React, { useState } from "react";
import { FormProvider, useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import InputField from "../../custom/inputfield";
import { useRegister, SendEmailResponse } from "@/mutations/auth/useAuthMutations";
import { toast } from "sonner";
import Link from "next/link";

const registerSchema = z.object({
  email: z.string().email("Invalid email address"),
});

type RegisterValues = z.infer<typeof registerSchema>;

interface RegisterStepProps {
  onSuccess: (email: string, isVerified?: boolean) => void;
}

const RegisterStep: React.FC<RegisterStepProps> = ({ onSuccess }) => {
  const { mutate: register, isPending } = useRegister();
  const [verifiedLink, setVerifiedLink] = useState<{ email: string; message: string } | null>(null);

  const methods = useForm<RegisterValues>({
    resolver: zodResolver(registerSchema),
    defaultValues: { email: "" },
  });

  const onSubmit = (data: RegisterValues) => {
    register(data, {
      onSuccess: (response: SendEmailResponse) => {
        const res: any = response;
        const message = res?.message?.toLowerCase() || "";
        const status = res?.errors?.status || res?.data?.status || res?.status || "";
        const code = res?.code || "";

        const isVerified =
          message.includes("verified") ||
          status === "verified" ||
          code === "ALREADY_VERIFIED";

        if (response.success) {
          toast.success(response.message || "OTP sent to your email!");
          onSuccess(data.email, isVerified);
        } else if (isVerified) {
          if (typeof window !== "undefined") {
            localStorage.setItem('reg_email', data.email);
          }
          setVerifiedLink({
            email: data.email,
            message: response.errors?.message || response.message || "Email is already verified. Please complete your profile."
          });
          methods.setError("email", {
            message: response.errors?.message || response.message || "Email is already verified."
          });
        } else {
          const errorMsg = response.errors?.message || response.message || "Failed to send OTP.";
          methods.setError("email", { message: errorMsg });
          toast.error(errorMsg);
        }
      },
      onError: (err: any) => {
        const responseData = err?.response?.data || {};
        const message = (responseData?.message || "").toLowerCase();
        const status = responseData?.errors?.status || responseData?.data?.status || responseData?.status || "";
        const code = responseData?.code || "";

        const isVerified =
          message.includes("verified") ||
          status === "verified" ||
          code === "ALREADY_VERIFIED";

        if (isVerified) {
          if (typeof window !== "undefined") {
            localStorage.setItem('reg_email', data.email);
          }
          setVerifiedLink({
            email: data.email,
            message: responseData?.errors?.message || responseData?.message || "Email is already verified. Please complete your profile."
          });
          methods.setError("email", {
            message: responseData?.errors?.message || responseData?.message || "Email is already verified."
          });
        } else {
          const errorMsg = responseData?.errors?.message || responseData?.message || err?.message || "An unexpected error occurred.";
          methods.setError("email", { message: errorMsg });
          toast.error(errorMsg);
        }
      },
    });
  };

  return (
    <div className="space-y-6">
      <FormProvider {...methods}>
        <form onSubmit={methods.handleSubmit(onSubmit)} className="space-y-5">
          <InputField
            name="email"
            label="Email Address"
            placeholder="example@mail.com"
            required
            disabled={isPending}
            type="email"
          />

          {verifiedLink && (
            <div className="flex flex-col gap-2 p-3 rounded-lg bg-primary/5 border border-primary/10">
              <p className="text-sm text-foreground font-source-sans">
                {verifiedLink.message}
              </p>
              <Link
                href="/auth/complete-profile"
                className="text-sm font-semibold text-primary hover:underline hover:text-primary/80 transition-colors w-fit"
              >
                Complete Your Profile Now →
              </Link>
            </div>
          )}

          <button
            type="submit"
            disabled={isPending}
            className="w-full bg-primary text-primary-foreground hover:bg-primary/90 font-medium py-2.5 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed font-source-sans"
          >
            {isPending ? "Sending OTP..." : "Get OTP"}
          </button>
        </form>
      </FormProvider>
    </div>
  );
};

export default RegisterStep;
