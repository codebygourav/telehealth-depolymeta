import { AppSidebar } from "@/components/app-sidebar";
import { Footer } from "@/components/layout/Footer";
import { Header } from "@/components/layout/Header";

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
        className="flex-1 min-h-screen container-max-width   px-5 sm:px-5 py-5 sm:py-5"
        style={{ marginLeft: "auto", marginRight: "auto", width: "100%"  }}
      >

        {children}
      </div>

      <Toaster richColors position="top-right" />
      <Footer />
    </>
  );
}
