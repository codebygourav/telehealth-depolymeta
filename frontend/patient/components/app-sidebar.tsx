"use client"

import * as React from "react"

import { NavMain } from "@/components/nav-main"
import { NavProjects } from "@/components/nav-projects"
import { NavUser } from "@/components/nav-user"
import { TeamSwitcher } from "@/components/team-switcher"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
  useSidebar,
} from "@/components/ui/sidebar"
import { LayoutDashboard, Stethoscope, CalendarCheck, FileText, User, Pill, LogOut } from "lucide-react"
import Image from "next/image"
import icon from "@/public/assets/icon/logo-light.png"
import iconSmall from "@/public/assets/icon/app-icon.png"
import { useSettings } from "@/context/settingsContext"
import { useAuth } from "@/context/userContext"
import Link from "next/link"

// This is sample data.
const data = {
  user: {
    name: "Patient",
    email: "patient@example.com",
    avatar: "/avatars/user.png",
  },
  navMain: [
    {
      title: "Dashboard",
      url: "/",
      icon: <LayoutDashboard />,
    },
    {
      title: "Find Doctors",
      url: "/find-doctors",
      icon: <Stethoscope />,
    },
    {
      title: "My Appointments",
      url: "/appointments",
      icon: <CalendarCheck />,
    },
    {
      title: "Medical Reports",
      url: "/medical-records",
      icon: <FileText />,
    },
    {
      title: "My Medicine",
      url: "/my-medicines",
      icon: <Pill />,
    },
    {
      title: "Person Info",
      icon: <User />,
      items: [
        {
          title: "Notifications",
          url: "/notifications",
        },
        {
          title: "My Profile",
          url: "/profile",
        },
        {
          title: "My Reviews",
          url: "/reviews",
        },
        {
          title: "Transactions",
          url: "/transactions",
        },
      ],
    },
  ],
};

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  const { state } = useSidebar();
  const isCollapsed = state === "collapsed";
  const { logout } = useAuth();
  const { settings } = useSettings();

  const teams = [
    {
      name: settings.appName || "CMC Team",
      logo: (
        <Image
          src={settings.logoUrl || icon}
          alt="Logo"
          width={200}
          height={200}
          className="w-full h-full object-contain rounded-lg"
          unoptimized
        />
      ),
      collapsedLogo: (
        <Image
          src={settings.faviconUrl || iconSmall}
          alt="Logo Small"
          width={200}
          height={200}
          className="w-full h-40 scale-200 object-contain rounded-lg"
          unoptimized
        />
      ),
      plan: "Enterprise",
    },
  ];

  return (
    <Sidebar
      collapsible="icon"
      {...props}
      className="transition-all duration-300 [--sidebar-width:17rem] [--sidebar-width-icon:4.5rem]"
    >
      <SidebarHeader className="border-b">
        {teams.length > 0 && (
          <TeamSwitcher teams={teams} />
        )}
      </SidebarHeader>

      <SidebarContent className="mt-5">
        {!isCollapsed && (
          <div className="px-5 mt-6 mb-2 text-xs font-semibold text-gray-400/80">
            Main Nav
          </div>
        )}
        <NavMain items={data.navMain} />
      </SidebarContent>

      <SidebarFooter>
        <div className="space-y-2">
          <NavUser user={data.user} />
          <Link
            href="/auth/login"
            onClick={async (event) => {
              event.preventDefault();
              await logout();
              window.location.href = "/auth/login";
            }}
            className="flex w-full items-center gap-2 rounded-xl border border-destructive/20 bg-destructive/5 px-3 py-2 text-sm font-medium text-destructive transition-colors hover:bg-destructive/10"
          >
            <LogOut className="h-4 w-4" />
            <span>Log out</span>
          </Link>
        </div>
      </SidebarFooter>

      <SidebarRail />
    </Sidebar>
  )
}
