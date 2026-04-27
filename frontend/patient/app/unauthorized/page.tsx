"use client";
import { Button } from "@/components/ui/button";
import { useRouter } from "next/navigation";
import { useAuth } from "@/context/userContext";

export default function UnauthorizedPage() {
    const router = useRouter();
    const { logout } = useAuth();
    return (

        <div className="flex flex-col items-center justify-center h-[60vh] text-center space-y-4">
            <p className="text-destructive font-semibold text-lg">
                Only patients can access this dashboard
            </p>

            <p className="text-muted-foreground max-w-md">
                You are currently not logged in as a patient. Please login with patient credentials to continue.
            </p>

            <Button onClick={() => { logout(); router.push("/auth/login"); }}>
                Login as Patient
            </Button>
        </div>
    );
}