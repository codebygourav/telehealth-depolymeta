import { AppSidebar } from "@/components/app-sidebar";
import { Footer } from "@/components/layout/Footer";
import { Header } from "@/components/layout/Header";
// import { Header } from "@/components/layout/Header";

import { SidebarInset, SidebarProvider } from "@/components/ui/sidebar";
import { Toaster } from "sonner";

export default function MainLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <>
      <Header />
      <div
        className="flex-1 min-h-screen pt-5 pb-5 container-max-width"
        style={{ marginLeft: "auto", marginRight: "auto", width: "100%" }}
      >
        {children}
      </div>

      <Toaster richColors position="top-right" />
      <Footer />
    </>
  );
}
