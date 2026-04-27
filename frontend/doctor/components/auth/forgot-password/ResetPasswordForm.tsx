"use client";
import React from "react";
import { useForm, FormProvider } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import InputField from "@/components/custom/inputfield";
import { Button } from "@/components/ui/button";
import { useResetPassword } from "@/mutations/useForgotPassword";
import { toast } from "sonner";
import { useRouter } from "next/navigation";

const resetPasswordSchema = z.object({
  password: z.string().min(8, "Password must be at least 8 characters"),
  password_confirmation: z.string().min(8, "Password must be at least 8 characters"),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Passwords do not match",
  path: ["password_confirmation"],
});

type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;

interface ResetPasswordFormProps {
  email: string;
  resetToken: string;
}

const ResetPasswordForm: React.FC<ResetPasswordFormProps> = ({ email, resetToken }) => {
  const { mutate, isPending } = useResetPassword();
  const router = useRouter();

  const methods = useForm<ResetPasswordFormData>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      password: "",
      password_confirmation: "",
    },
  });

  const onSubmit = async (data: ResetPasswordFormData) => {
    mutate({
      email,
      reset_token: resetToken,
      password: data.password,
      password_confirmation: data.password_confirmation,
    }, {
      onSuccess: () => {
        toast.success("Password reset successfully. Please login with your new password.");
        router.push("/auth/login");
      },
      onError: (error: any) => {
        console.error(error);
        const errorMsg = error?.response?.data?.errors?.message;
        if (errorMsg) {
          methods.setError("password", { type: "server", message: errorMsg });
        } else {
          toast.error(error?.response?.data?.message || "Error resetting password. Please try again.");
        }
      },
    });
  };

  return (
    <FormProvider {...methods}>
      <form onSubmit={methods.handleSubmit(onSubmit)} className="space-y-4">
        <InputField
          name="password"
          label="New Password"
          type="password"
          placeholder="Enter new password"
          required
        />
        <InputField
          name="password_confirmation"
          label="Confirm Password"
          type="password"
          placeholder="Confirm your new password"
          required
        />
        <Button type="submit" className="w-full" disabled={isPending}>
          {isPending ? "Resetting..." : "Reset Password"}
        </Button>
      </form>
    </FormProvider>
  );
};

export default ResetPasswordForm;
