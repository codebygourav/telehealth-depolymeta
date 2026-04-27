"use client"

import * as React from "react"

import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar"
import { useRouter } from "next/navigation"
import { ArrowLeft } from "lucide-react"

export function TeamSwitcher({
  teams,
}: {
  teams: {
    name: string
    logo: React.ReactNode
    collapsedLogo?: React.ReactNode
    plan: string
  }[]
}) {
  const { toggleSidebar, state } = useSidebar()
  const isCollapsed = state === "collapsed"

  const router = useRouter()

  const activeTeam = teams[0]

  if (!activeTeam) return null

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <SidebarMenuButton
          size="lg"
          onClick={() => router.push("/")}
          className={`w-full! flex items-center ${isCollapsed ? "justify-center gap-0 relative z-50" : "justify-between"
            }`}
        >
          <div
            className={`flex items-center justify-center rounded-lg transition-all duration-300 ${isCollapsed ? "w-auto h-auto mx-auto" : "w-32 h-9"
              }`}
          >
            {isCollapsed ? activeTeam.collapsedLogo : activeTeam.logo}
            {/* {isCollapsed ? activeTeam.collapsedLogo || activeTeam.logo : activeTeam.logo} */}
          </div>

          <ArrowLeft
            onClick={(e) => {
              e.stopPropagation()
              toggleSidebar()
            }}
            className={`w-4 h-4 cursor-pointer rounded p-0.5 text-black bg-gray-200 transition-all duration-300 ${isCollapsed ? "absolute -right-6 top-2" : ""
              }`}
          />
        </SidebarMenuButton>
      </SidebarMenuItem>
    </SidebarMenu>
  )
}