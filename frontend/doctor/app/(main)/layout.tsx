import { Footer } from "@/components/layout/Footer";
import { Header } from "@/components/layout/Header";
import { Toaster } from "sonner";

export default function MainLayout({ children }: { children: React.ReactNode }) {
    return (
        <>
            <Header />
            <main className="flex-1 p-5 pt-10 min-h-screen container mx-auto">{children}</main>
            <Toaster richColors position="top-right" />
            <Footer />
        </>
    );
}
