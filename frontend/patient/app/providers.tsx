"use client";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { UserProvider } from "@/context/userContext";
import { useState } from "react";

import { SettingsProvider } from "@/context/settingsContext";

export function Providers({ children }: { children: React.ReactNode }) {
    const [queryClient] = useState(
        () =>
            new QueryClient({
                defaultOptions: {
                    queries: {
                        staleTime: 60 * 1000,
                        gcTime: 10 * 60 * 1000,
                        retry: 1,
                        refetchOnWindowFocus: false,
                        refetchOnReconnect: false,
                        refetchInterval: false,
                    },
                },
            }),
    );
    
    return (
        <QueryClientProvider client={queryClient}>
            <SettingsProvider>
                <UserProvider>
                    {children}
                </UserProvider>
            </SettingsProvider>
        </QueryClientProvider>
    );
}
