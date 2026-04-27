"use client"

import Image from "next/image"
import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import AuthForm from "@/components/auth/AuthForm"
import AuthLayout from "@/components/auth/AuthLayout"
import { useLogin } from "@/mutations/useLogin"
import { useAuth } from "@/context/userContext"
import { User, UserRole } from "@/types/user-context"

interface LoginFormData {
    email: string
    password: string
}

const LoginPage = () => {

    const router = useRouter()
    const { login, user } = useAuth();
    const { mutate: signIn, isPending, isError, error } = useLogin();
    const [mounted, setMounted] = useState(false)
    const [imageError, setImageError] = useState(false)
    const [emailverified, setEmailverified] = useState(false);

    useEffect(() => {
        setMounted(true)
    }, [])

    const fields = [
        {
            name: "email",
            type: "email",
            label: "Email Address",
            placeholder: "Enter your email",
            required: true,
            validation: {
                pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                message: "Please enter a valid email address",
            },
        },
        {
            name: "password",
            type: "password",
            label: "Password",
            placeholder: "Enter your password",
            required: true,
            validation: {
                minLength: 6,
                message: "Password must be at least 6 characters",
            },
        },
    ]

    const handleLogin = (formData: LoginFormData): Promise<void> => {
        return new Promise((resolve) => {
            signIn(formData, {
                onSuccess: async (responseData) => {

                    // The actual payload might be deeply nested depending on API variations.
                    // We extract it defensively.
                    const payload = (responseData as any)?.data || responseData;
                    const user = (payload as any)?.user || payload;
                    const token = (responseData as any)?.token || (payload as any)?.token;
                    
                    if (!user) {
                        console.error('User object is undefined in response:', responseData);
                        return;
                    }

                    const role: UserRole = user.role as UserRole;

                    const userData: User = {
                        id: user.id || "",
                        first_name: user.first_name || "",
                        last_name: user.last_name || "",
                        email: user.email || "",
                        role,
                        gender: user.gender,
                        date_of_birth: user.date_of_birth,
                        phone: user.phone,
                        patient_id: user.patient_id,
                        doctor_id: user.doctor_id,
                        status: user.status ?? "",
                        avatar: user.avatar,
                        address: {
                            address: user.address?.address,
                            area: user.address?.area,
                            city: user.address?.city,
                            landmark: user.address?.landmark,
                            pincode: user.address?.pincode,
                            state: user.address?.state,
                        },
                    };

                    // console.log("Render Login Screen", responseData);

                    await login(userData, token || "");

                    router.push("/");

                    resolve();
                },
                onError: (error: any) => {
                    const err = (error as any)?.response?.data?.errors?.message;
                    console.log('err', err);
                    if (error.response?.data?.errors?.status === "verified") {
                        setEmailverified(true);
                    }
                    resolve();
                },
            });
        });
    };

    return (
        <AuthLayout title="" subtitle="">
            <div className="mb-6 text-center">
                <div className="mb-4 flex justify-center">
                    {mounted && (
                        <Image
                            src="/icons/logo-light.png"
                            alt="Company Logo"
                            width={120}
                            height={40}
                            priority
                            className="h-16 w-auto"
                            onError={() => setImageError(true)}
                            unoptimized
                        />
                    )}
                </div>

                {imageError && (
                    <div className="mb-4 flex justify-center">
                        <div className="flex h-16 w-16 items-center justify-center rounded-xl bg-primary">
                            <span className="text-2xl font-bold text-primary-foreground">
                                A
                            </span>
                        </div>
                    </div>
                )}

                <p className="text-sm text-muted-foreground">
                    Sign in to your account to continue
                </p>

                {isError && (
                    <div className="mt-3 rounded-md bg-red-50 p-3">
                        <p className="text-sm text-red-600">
                            {((error as any)?.response?.data?.errors?.message ??
                                (error as any)?.message ??
                                "Login failed. Please check your credentials.")}
                        </p>
                    </div>
                )}
            </div>

            <AuthForm
                fields={fields}
                buttonText={isPending ? "Signing In..." : "Sign In"}
                onSubmit={handleLogin}
                showForgotPassword={true}
                alternateLink={{
                    text: "Don't have an account?",
                    href: "/register",
                    linkText: "Sign up",
                }}
            />
        </AuthLayout>
    )
}

export default LoginPage