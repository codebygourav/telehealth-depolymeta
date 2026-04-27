"use client";

import { useAppProfileScreens } from "@/queries/useAppProfileScreens";
import { useAuth } from "@/context/userContext";

const Page = () => {
    const { token } = useAuth();
    const { data, isLoading, error } = useAppProfileScreens(token || undefined);

    const termsAndConditionsData = data?.term_and_conditions;

    return (
        <div className="flex items-center justify-center h-full min-h-screen">
            {isLoading ? (
                'loading...'
            ) : error ? (
                <p className="text-red-500">
                    {((error as any)?.response?.data?.errors?.message ??
                        (error as any)?.message ??
                        "Error loading content")}
                </p>
            ) : termsAndConditionsData ? (
                <div className="html-content"
                    dangerouslySetInnerHTML={{ __html: termsAndConditionsData }}
                />
            ) : "No Content Available"
            }
        </div>
    )
}

export default Page