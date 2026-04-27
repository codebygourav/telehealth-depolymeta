import { AppSidebar } from "@/components/app-sidebar";
import { Footer } from "@/components/layout/Footer";
import { Header } from "@/components/layout/Header";
// import { Header } from "@/components/layout/Header";

import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar";
import { Toaster } from "sonner";

export default function MainLayout({ children }: { children: React.ReactNode }) {

    return (
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset className="flex min-h-screen w-auto md:pl-5 pl-0 flex-col overflow-hidden">
                <Header />
                <div className="flex-1 p-5 lg:py-8 overflow-y-auto">{children}</div>
                <Toaster richColors position="top-right" />
                <Footer />
            </SidebarInset>
        </SidebarProvider>
    );
}
