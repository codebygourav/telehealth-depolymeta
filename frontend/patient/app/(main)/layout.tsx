import { Footer } from "@/components/layout/Footer";
import { Header } from "@/components/layout/Header";
import { Toaster } from "sonner";

export default function MainLayout({ children }: { children: React.ReactNode }) {
    return (
        <>
            <Header />
            <div className="flex-1 min-h-screen px-5 sm:px-5 py-5 sm:py-5 mx-auto w-full">
                {children}
            </div>
            <Toaster richColors position="top-right" />
            <Footer />
        </>
    );
}
