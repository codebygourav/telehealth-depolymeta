"use client";
import { UserProvider } from "@/context/userContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useState } from "react";

export function Providers({ children }: { children: React.ReactNode }) {

    const [queryClient] = useState(() => new QueryClient({
        defaultOptions: {
            queries: {
                // Keep data fresh for 1 minute before considering it stale
                staleTime: 60 * 1000,
                // Keep unused data in cache for 10 minutes (reduces garbage collection frequency)
                gcTime: 10 * 60 * 1000,
                // Don't retry failed requests indkefinitely
                retry: 2,
                // Refetch on window focus (good for real-time apps, disable if not needed)
                refetchOnWindowFocus: false,
                // Prevent memory buildup from accumulating queries
                refetchInterval: false,
            },
        },
    }));

    return (
        <QueryClientProvider client={queryClient}>
            <UserProvider>
                {children}
            </UserProvider>
        </QueryClientProvider>
    );
}