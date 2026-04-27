"use client";
import InputField from "@/components/custom/inputfield";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/userContext";
import { useChangePassword } from "@/mutations/useChangePassword";
import { zodResolver } from "@hookform/resolvers/zod";
import { ArrowLeft, Eye, EyeOff } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { useForm, FormProvider } from "react-hook-form";
import { toast } from "sonner";
import * as z from "zod";

const changePasswordSchema = z
  .object({
    current_password: z.string().min(1, "Current password is required"),
    new_password: z.string().min(6, "Password must be at least 6 characters"),
    new_password_confirmation: z
      .string()
      .min(6, "Password must be at least 6 characters"),
  })
  .refine((data) => data.new_password === data.new_password_confirmation, {
    message: "New passwords do not match",
    path: ["new_password_confirmation"],
  });

type ChangePasswordFormData = z.infer<typeof changePasswordSchema>;

export default function ChangePasswordPage() {
  const router = useRouter();
  const { mutate, isPending } = useChangePassword();
  const { logout } = useAuth();

  const [showPasswords, setShowPasswords] = useState<Record<string, boolean>>(
    {},
  );

  const togglePasswordVisibility = (fieldName: string) => {
    setShowPasswords((prev) => ({
      ...prev,
      [fieldName]: !prev[fieldName],
    }));
  };

  const methods = useForm<ChangePasswordFormData>({
    resolver: zodResolver(changePasswordSchema),
    defaultValues: {
      current_password: "",
      new_password: "",
      new_password_confirmation: "",
    },
  });

  const onSubmit = async (data: ChangePasswordFormData) => {
    mutate(data, {
      onSuccess: async (response) => {
        toast.success(
          response.message ||
          "Password changed successfully! Please login again.",
        );
        await logout(); // Clear cookies and token, causing a hard redirect to login internally
      },
      onError: (error: any) => {
        console.error(error);
        const errorMsg = error?.response?.data?.errors?.message;

        if (errorMsg) {
          // As per your request, binding the extracted error message directly to the bottom of the current password field
          methods.setError("current_password", {
            type: "server",
            message: errorMsg,
          });
        } else {
          toast.error(
            error?.response?.data?.message ||
            "Error changing password. Please try again.",
          );
        }
      },
    });
  };

  return (
    <div className="animate-fade-in py-8">
      <div className="flex items-center space-x-4 mb-6">
        <Button variant="ghost" size="icon" asChild>
          <Link href="/profile">
            <ArrowLeft className="h-5 w-5" />
          </Link>
        </Button>
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Change Password</h1>
          <p className="text-sm text-muted-foreground">
            Update your account password securely
          </p>
        </div>
      </div>

      <div className="bg-card border border-border rounded-xl shadow-sm p-6 overflow-hidden">
        <FormProvider {...methods}>
          <form onSubmit={methods.handleSubmit(onSubmit)} className="space-y-6">
            <div className="relative">
              <InputField
                name="current_password"
                label="Current Password"
                type={showPasswords["current_password"] ? "text" : "password"}
                placeholder="Enter your current password"
                required
              />
              <button
                type="button"
                onClick={() => togglePasswordVisibility("current_password")}
                className="absolute right-3 text-muted-foreground hover:text-foreground transition-colors"
                tabIndex={-1}
              >
                {showPasswords["current_password"] ? (
                  <EyeOff className="h-4 w-4" />
                ) : (
                  <Eye className="h-4 w-4" />
                )}
              </button>
            </div>

            <div className="grid gap-6 md:grid-cols-2">
              <div className="relative">
                <InputField
                  name="new_password"
                  label="New Password"
                  type={showPasswords["new_password"] ? "text" : "password"}
                  placeholder="Enter new password"
                  required
                />
                <button
                  type="button"
                  onClick={() => togglePasswordVisibility("new_password")}
                  className="absolute right-3 top-9 text-muted-foreground hover:text-foreground transition-colors"
                  tabIndex={-1}
                >
                  {showPasswords["new_password"] ? (
                    <EyeOff className="h-4 w-4" />
                  ) : (
                    <Eye className="h-4 w-4" />
                  )}
                </button>
              </div>

              <div className="relative">
                <InputField
                  name="new_password_confirmation"
                  label="Confirm New Password"
                  type={
                    showPasswords["new_password_confirmation"]
                      ? "text"
                      : "password"
                  }
                  placeholder="Confirm your new password"
                  required
                />
                <button
                  type="button"
                  onClick={() =>
                    togglePasswordVisibility("new_password_confirmation")
                  }
                  className="absolute right-3 top-9 text-muted-foreground hover:text-foreground transition-colors"
                  tabIndex={-1}
                >
                  {showPasswords["new_password_confirmation"] ? (
                    <EyeOff className="h-4 w-4" />
                  ) : (
                    <Eye className="h-4 w-4" />
                  )}
                </button>
              </div>
            </div>

            <div className="flex justify-end pt-4">
              <Button
                type="submit"
                disabled={isPending}
                className="w-full sm:w-auto min-w-[150px]"
              >
                {isPending ? "Changing..." : "Change Password"}
              </Button>
            </div>
          </form>
        </FormProvider>
      </div>
    </div>
  );
}
