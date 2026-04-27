"use client";

import {
    Avatar,
    AvatarFallback,
    AvatarImage,
    Badge,
    Button,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    Separator,
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/components/ui";
import { useAuth } from "@/context/userContext";
import { useNotifications } from "@/queries/notifications";
import { cn } from "@/lib/utils";
import icon from "@/public/assets/icon/logo-light.png";
import type { NavItem } from "@/types/header";
import {
    Bell,
    Calendar,
    LayoutDashboard,
    LogOut,
    Menu,
    MessageSquare,
    User as UserIcon,
} from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { NotificationDropdown } from "./NotificationDropdown";

export function Header() {
    const [isScrolled, setIsScrolled] = useState(false);
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    const router = useRouter();

    // Use actual media query for responsive check
    const [isDesktop, setIsDesktop] = useState(true);

    useEffect(() => {
        const handleResize = () => {
            setIsDesktop(window.innerWidth >= 1024); // lg breakpoint
        };
        handleResize();
        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, []);

    // Listen scroll for sticky effect (optional, or remove if unused)
    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 8);
        };
        window.addEventListener("scroll", handleScroll);
        return () => window.removeEventListener("scroll", handleScroll);
    }, []);

    const pathname = usePathname();
    const { user, initializing, logout } = useAuth();
    const { data: notificationsData } = useNotifications();
    const unreadCount = notificationsData?.meta?.total_unread ?? 0;

    // For notification label (Name fallback)
    const name =
        user && (user.first_name || user.last_name)
            ? `${user.role === "doctor" ? "Dr. " : ""}${user.first_name ?? ""} ${user.last_name ?? ""}`.trim()
            : "User";


    const navItems: NavItem[] = [
        {
            title: "Dashboard",
            href: "/",
            icon: <LayoutDashboard className="h-4 w-4" />,
        },
        {
            title: "My Schedules",
            href: "/my-schedules",
            icon: <Calendar className="h-4 w-4" />,
        },
        {
            title: "Appointments",
            href: "/appointments",
            icon: <UserIcon className="h-4 w-4" />,
        },
        {
            title: "Feedbacks",
            href: "/feedbacks",
            icon: <MessageSquare className="h-4 w-4" />,
        },
    ];


  return (
    <header
      className={cn(
        "sticky top-0 z-50 w-full border-b border-border bg-card shadow-sm",
        isScrolled
          ? "bg-background/95 backdrop-blur supports-backdrop-filter:bg-background/60 border-b"
          : "bg-background",
      )}
    >
      <div className="flex h-16 items-center justify-between px-3 sm:px-4 md:px-6 lg:px-8 container mx-auto">
        {/* Logo and App Name */}
        <Link href="/" className="flex items-center space-x-2 shrink-0">
          <Image
            src={icon}
            alt="Logo"
            width={180}
            height={32}
            className="w-28 sm:w-32 md:w-44 h-auto"
            priority
          />
        </Link>

        {/* Desktop Navigation */}
        {isDesktop && (
          <nav className="flex items-center gap-2 lg:gap-4">
            {navItems.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  "relative flex items-center gap-2 rounded-md px-2 lg:px-3 py-2 text-xs lg:text-sm font-medium transition-colors",
                  pathname === item.href
                    ? "bg-primary text-primary-foreground"
                    : "text-muted-foreground bg-accent hover:bg-accent hover:text-accent-foreground",
                )}
              >
                {item.icon}
                <span className="hidden sm:inline">{item.title}</span>
                {item.badge ? (
                  <Badge
                    variant={pathname === item.href ? "secondary" : "default"}
                    className="ml-auto flex h-5 w-5 items-center justify-center text-[10px] bg-primary/10! rounded-full p-4"
                  >
                    {Number(item.badge) > 99 ? "99+" : item.badge}
                  </Badge>
                ) : null}
              </Link>
            ))}
          </nav>
        )}

        {/* Action Buttons */}
        <div className="flex items-center gap-2 sm:gap-4">
          <NotificationDropdown />

          {user || initializing ? (
            <div className="flex items-center gap-2 sm:gap-3">
              {/* Desktop User Info - Only visible on desktop */}
              <div className="flex-col text-right hidden lg:flex">
                <span className="text-sm font-semibold leading-none">
                  {initializing ? "unknown name" : name}
                </span>
                <span className="text-[11px] text-muted-foreground font-medium">
                  {initializing
                    ? "unknown email"
                    : user?.email || "healthcare@info.test"}
                </span>
              </div>

              {/* Desktop Avatar - Only visible on desktop */}
              <div className="hidden lg:block">
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button
                      variant="ghost"
                      className="relative h-8 w-8 rounded-full border-0 p-0 hover:bg-transparent"
                    >
                      <Avatar className="h-8 w-8">
                        <AvatarImage src={user?.avatar || ""} alt={name} />
                        <AvatarFallback>
                          <UserIcon className="h-4 w-4 text-muted-foreground" />
                        </AvatarFallback>
                      </Avatar>
                    </Button>
                  </DropdownMenuTrigger>

                  <DropdownMenuContent className="w-56" align="end" forceMount>
                    <DropdownMenuGroup>
                      <DropdownMenuItem asChild>
                        <Link href="/profile" className="cursor-pointer">
                          <UserIcon className="mr-2 h-4 w-4" />
                          <span>Profile</span>
                        </Link>
                      </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                      className="cursor-pointer text-destructive focus:text-destructive"
                      onClick={async () => {
                        await logout();
                        window.location.href = "/auth/login";
                      }}
                      disabled={initializing}
                    >
                      <LogOut className="mr-2 h-4 w-4" />
                      <span>Log out</span>
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>

              {/* Mobile Menu Button - Only visible on mobile */}
              {!isDesktop && (
                <Sheet
                  open={isMobileMenuOpen}
                  onOpenChange={setIsMobileMenuOpen}
                >
                  <SheetTrigger asChild>
                    <Button variant="ghost" size="icon" className="h-10 w-10">
                      <Menu className="h-6! w-6!" />
                    </Button>
                  </SheetTrigger>

                  <SheetContent
                    side="right"
                    className="w-87.5 p-0"
                  >
                    {/* Header with Logo and Close */}
                    <SheetHeader className="border-b p-4">
                      <SheetTitle className="sr-only">Menu</SheetTitle>
                      {/* Profile Section - Mobile Menu */}
                      <div className="bg-muted/10">
                        <div className="flex items-center gap-3">
                          <Avatar className="h-12 w-12">
                            <AvatarImage src={user?.avatar || ""} alt={name} />
                            <AvatarFallback className="bg-primary/10 text-primary">
                              {name.charAt(0) || "U"}
                            </AvatarFallback>
                          </Avatar>
                          <div className="flex-1 min-w-0">
                            <p className="text-base font-semibold truncate">
                              {initializing ? "Loading..." : name}
                            </p>
                            <p className="text-sm text-muted-foreground truncate">
                              {initializing ? "Loading..." : user?.email || "healthcare@info.test"}
                            </p>
                            <Link
                              href="/profile"
                              onClick={() => setIsMobileMenuOpen(false)}
                              className="inline-block mt-1 text-sm text-primary hover:underline"
                            >
                              View Profile →
                            </Link>
                          </div>
                        </div>
                      </div>
                    </SheetHeader>

                    {/* Navigation Items */}
                    <nav className="flex flex-col gap-1 p-4">
                      {navItems.map((item) => (
                        <Link
                          key={item.href}
                          href={item.href}
                          onClick={() => setIsMobileMenuOpen(false)}
                          className={cn(
                            "flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors",
                            pathname === item.href
                              ? "bg-primary text-primary-foreground"
                              : "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
                          )}
                        >
                          {item.icon}
                          <span>{item.title}</span>
                          {item.badge ? (
                            <Badge className="ml-auto">
                              {Number(item.badge) > 99 ? "99+" : item.badge}
                            </Badge>
                          ) : null}
                        </Link>
                      ))}

                      <Separator className="my-2" />

                      {/* Logout Button */}
                      <button
                        onClick={async () => {
                          await logout();
                          window.location.href = "/auth/login";
                        }}
                        className="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors w-full"
                      >
                        <LogOut className="h-4 w-4" />
                        <span>Log out</span>
                      </button>
                    </nav>
                  </SheetContent>
                </Sheet>
              )}
            </div>
          ) : (
            !initializing && (
              <Link
                href="/auth/login"
                className="text-sm font-medium text-primary hover:underline px-3 py-2"
              >
                Sign In
              </Link>
            )
          )}
        </div>
      </div>
    </header>
  );
}
