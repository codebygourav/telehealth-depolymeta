"use client";

import React, { useEffect, useState } from "react";
import AuthLayout from "@/components/auth/AuthLayout";
import CompleteProfileStep from "@/components/auth/registration/CompleteProfileStep";

const CompleteProfilePage = () => {
    const [email, setEmail] = useState("");

    useEffect(() => {
        if (typeof window !== "undefined") {
            const storedEmail = localStorage.getItem('reg_email');
            if (storedEmail) {
                setEmail(storedEmail);
            }
        }
    }, []);

    return (
        <AuthLayout title="" subtitle="">
            <div className="w-full">
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
            </div>
        </AuthLayout>
    );
};

export default CompleteProfilePage;
