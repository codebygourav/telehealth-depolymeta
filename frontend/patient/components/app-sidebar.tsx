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
import { LayoutDashboard, Stethoscope, CalendarCheck, FileText, User, Pill } from "lucide-react"
import Image from "next/image"
import icon from "@/public/assets/icon/logo-light.png"
import iconSmall from "@/public/assets/icon/app-icon.png"

// This is sample data.
// const data = {
//   user: {
//     name: "shadcn",
//     email: "m@example.com",
//     avatar: "/avatars/shadcn.jpg",
//   },
//   teams: [
//     {
//       name: "Acme Inc",
//       logo: (
//         <GalleryVerticalEndIcon
//         />
//       ),
//       plan: "Enterprise",
//     },
//     {
//       name: "Acme Corp.",
//       logo: (
//         <AudioLinesIcon
//         />
//       ),
//       plan: "Startup",
//     },
//     {
//       name: "Evil Corp.",
//       logo: (
//         <TerminalIcon
//         />
//       ),
//       plan: "Free",
//     },
//   ],
//   navMain: [
//     {
//       title: "Dashboard",
//       url: "#",
//       icon: (
//         <TerminalSquareIcon
//         />
//       ),
//       isActive: true,
//       items: [
//         {
//           title: "History",
//           url: "#",
//         },
//         {
//           title: "Starred",
//           url: "#",
//         },
//         {
//           title: "Settings",
//           url: "#",
//         },
//       ],
//     },
//     {
//       title: "Models",
//       url: "#",
//       icon: (
//         <BotIcon
//         />
//       ),
//       items: [
//         {
//           title: "Genesis",
//           url: "#",
//         },
//         {
//           title: "Explorer",
//           url: "#",
//         },
//         {
//           title: "Quantum",
//           url: "#",
//         },
//       ],
//     },
//     {
//       title: "Documentation",
//       url: "#",
//       icon: (
//         <BookOpenIcon
//         />
//       ),
//       items: [
//         {
//           title: "Introduction",
//           url: "#",
//         },
//         {
//           title: "Get Started",
//           url: "#",
//         },
//         {
//           title: "Tutorials",
//           url: "#",
//         },
//         {
//           title: "Changelog",
//           url: "#",
//         },
//       ],
//     },
//     {
//       title: "Settings",
//       url: "#",
//       icon: (
//         <Settings2Icon
//         />
//       ),
//       items: [
//         {
//           title: "General",
//           url: "#",
//         },
//         {
//           title: "Team",
//           url: "#",
//         },
//         {
//           title: "Billing",
//           url: "#",
//         },
//         {
//           title: "Limits",
//           url: "#",
//         },
//       ],
//     },
//   ],
//   projects: [
//     {
//       name: "Design Engineering",
//       url: "#",
//       icon: (
//         <FrameIcon
//         />
//       ),
//     },
//     {
//       name: "Sales & Marketing",
//       url: "#",
//       icon: (
//         <PieChartIcon
//         />
//       ),
//     },
//     {
//       name: "Travel",
//       url: "#",
//       icon: (
//         <MapIcon
//         />
//       ),
//     },
//   ],
// }

const data = {
  user: {
    name: "Patient",
    email: "patient@example.com",
    avatar: "/avatars/user.png",
  },
  teams: [
    {
      name: "CMC Team",
      logo: (
        <Image
          src={icon}
          alt="Logo"
          width={200}
          height={200}
          className="w-full h-full object-contain rounded-lg"
        />
      ),
      collapsedLogo: (
        <Image
          src={iconSmall}
          alt="Logo Small"
          width={200}
          height={200}
          className="w-full h-40 scale-200 object-contain rounded-lg"
        />
      ),
      plan: "Enterprise",
    },
  ],
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


    // {
    //   title: "Notifications",
    //   url: "/notifications",
    //   icon: <Settings2Icon />,
    // },
    // {
    //   title: "My Profile",
    //   url: "/profile",
    //   icon: <Settings2Icon />,
    // },
    // {
    //   title: "My Reviews",
    //   url: "/reviews",
    //   icon: <Settings2Icon />,
    // },
    // {
    //   title: "Transactions",
    //   url: "/transactions",
    //   icon: <Settings2Icon />,
    // },


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
  return (
    <Sidebar
      collapsible="icon"
      {...props}
      className="transition-all duration-300 [--sidebar-width:17rem] [--sidebar-width-icon:4.5rem]"
    >
      <SidebarHeader className="border-b">
        {data?.teams?.length > 0 && (
          <TeamSwitcher teams={data.teams} />
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
        <NavUser user={data.user} />
      </SidebarFooter>

      <SidebarRail />
    </Sidebar>
  )
}
