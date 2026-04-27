// export default function UnauthorizedPage() {
//     return (
//         <div className="flex h-screen items-center justify-center">
//             <h1 className="text-xl font-semibold text-red-600">
//                 Only a doctor can access this dashboard.
//             </h1>
//         </div>
//     );
// }

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
                Only Doctors can access this dashboard
            </p>

            <p className="text-muted-foreground max-w-md">
                You are currently not logged in as a doctor. Please login with doctor credentials to continue.
            </p>

            <Button onClick={() => { logout(); router.push("/auth/login"); }}>
                Login as Doctor
            </Button>
        </div>
    );
}