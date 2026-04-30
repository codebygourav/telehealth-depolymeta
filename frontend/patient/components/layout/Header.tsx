"use client";

import { Button } from "@/components/ui";
import { useAuth } from "@/context/userContext";
import { cn } from "@/lib/utils";
import logo from "@/public/assets/icon/logo-light.png";
import { useUnreadCount } from "@/queries/useNotifications";
import type { NavItem } from "@/types/header";

import {
    BellCheck,
    CalendarCheck,
    FileText,
    LayoutDashboard,
    Pill,
    Stethoscope,
} from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { HeaderNavLinks } from "./HeaderNavLinks";
import { HeaderUserProfileMenu } from "./HeaderUserProfileMenu";


export function Header() {
    const [isScrolled, setIsScrolled] = useState(false);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const { data: unreadData } = useUnreadCount();
    const { user, initializing, logout } = useAuth();
    const router = useRouter();
    const pathname = usePathname();

    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 8);
        };

        window.addEventListener("scroll", handleScroll);
        return () => window.removeEventListener("scroll", handleScroll);
    }, []);

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
            title: "Find Doctors",
            href: "/find-doctors",
            icon: <Stethoscope className="h-4 w-4" />,
        },
        {
            title: "My Appointments",
            href: "/appointments",
            icon: <CalendarCheck className="h-4 w-4" />,
        },
        {
            title: "Medical Reports",
            href: "/medical-records",
            icon: <FileText className="h-4 w-4" />,
        },
        {
            title: "My Medicine",
            href: "/my-medicines",
            icon: <Pill className="h-4 w-4" />,
        },
    ];

    const isActivePath = (href: string) => {
        if (href === "/") return pathname === "/";
        return pathname === href || pathname.startsWith(`${href}/`);
    };

    const handleLogout = async () => {
        await logout();
        router.push("/auth/login");
    };

    return (
        <header
            className={cn(
                "sticky top-0 z-50 w-full border-b border-border/60 bg-background/95 backdrop-blur supports-backdrop-filter:bg-background/80",
                isScrolled && "shadow-sm",
            )}
        >
            <div className="mx-auto flex h-[72px] container-max-width items-center gap-4 px-4 sm:px-6">
       
       
                <Link href="/" className="flex shrink-0 items-center">
                    <Image
                        src={logo}
                        alt="CMC Telehealth"
                        width={190}
                        height={42}
                        className="h-10 w-auto object-contain"
                        priority
                    />
                </Link>

                <HeaderNavLinks
                    items={navItems}
                    pathname={pathname}
                    mobileMenuOpen={mobileMenuOpen}
                    setMobileMenuOpen={setMobileMenuOpen}
                    isActivePath={isActivePath}
                />

                <div className="ml-auto flex items-center gap-3 sm:gap-4">
                    <Link href="/notifications" className="relative">
                        <Button
                            variant="outline"
                            size="icon"
                            className={cn(
                                "h-9 w-10 global-radius border-[#E7E8EB] bg-background hover:bg-foreground/10 text-foreground transition-all duration-100",
                                pathname === "/notifications" && "border-primary/20 bg-primary/5 ",
                            )}
                        >
                            <span className="relative block">
                                <BellCheck className="h-7 w-7" />
                            </span>
                        </Button>
                        {!!unreadData && unreadData > 0 && (
                            <span className="absolute right-2.5 top-4.5 h-1.5 w-1.5 rounded-full bg-red-500" />
                        )}
                    </Link>

                    {user || initializing ? (
                        <HeaderUserProfileMenu
                            user={user}
                            initializing={initializing}
                            name={name}
                            onLogout={handleLogout}
                        />
                    ) : (
                        <Link
                            href="/auth/login"
                            className="rounded-2xl border border-border/70 px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                        >
                            Sign In
                        </Link>
                    )}
                </div>
            </div>
        </header>
    );
}
