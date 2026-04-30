"use client";

import {
    Badge,
    Button,
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/components/ui";
import { cn } from "@/lib/utils";
import type { NavItem } from "@/types/header";
import { Bell, Menu } from "lucide-react";
import Link from "next/link";
import type { Dispatch, SetStateAction } from "react";

interface HeaderNavLinksProps {
    items: NavItem[];
    pathname: string;
    mobileMenuOpen: boolean;
    setMobileMenuOpen: Dispatch<SetStateAction<boolean>>;
    isActivePath: (href: string) => boolean;
}

export function HeaderNavLinks({
    items,
    pathname,
    mobileMenuOpen,
    setMobileMenuOpen,
    isActivePath,
}: HeaderNavLinksProps) {
    return (
    
        <>
            <nav className="hidden flex-1 items-center justify-center lg:flex">
                <div className="flex items-center gap-2 rounded-2xl p-1">
                    {items.map((item) => {
                        const isActive = isActivePath(item.href);

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    "inline-flex items-center gap-2 global-radius px-4 py-2 text-sm font-semibold transition-all duration-200",
                                    isActive
                                        ? "bg-primary text-primary-foreground shadow-sm"
                                        : "text-foreground/70 hover:bg-primary hover:text-white transition-all duration-100 bg-secondary-menu-color",
                                )}
                            >
                                {item.icon}
                                <span className="whitespace-nowrap">{item.title}</span>
                                {item.badge ? (
                                    <Badge
                                        variant={isActive ? "secondary" : "default"}
                                        className={cn(
                                            "ml-1 rounded-full px-2 py-0 text-[10px]",
                                            isActive && "bg-primary-foreground/15 text-primary-foreground",
                                        )}
                                    >
                                        {Number(item.badge) > 99 ? "99+" : item.badge}
                                    </Badge>
                                ) : null}
                            </Link>
                        );
                    })}
                </div>
            </nav>

            <div className="lg:hidden">
                <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                    <SheetTrigger asChild>
                        <Button
                            variant="outline"
                            size="icon"
                            className="h-11 w-11 rounded-2xl border-border/70 bg-background shadow-sm"
                        >
                            <Menu className="h-5 w-5" />
                        </Button>
                    </SheetTrigger>

                    <SheetContent side="right" className="w-[320px] border-l border-border/60 px-0">
                        <SheetHeader className="px-5 pt-6 text-left">
                            <SheetTitle className="text-base font-semibold">
                                Patient Navigation
                            </SheetTitle>
                        </SheetHeader>

                        <div className="mt-6 flex flex-col gap-2 px-3">
                            {items.map((item) => {
                                const isActive = isActivePath(item.href);

                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        onClick={() => setMobileMenuOpen(false)}
                                        className={cn(
                                            "flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition-all duration-200",
                                            isActive
                                                ? "bg-primary text-primary-foreground shadow-sm"
                                                : "text-foreground/75 hover:bg-muted hover:text-foreground",
                                        )}
                                    >
                                        {item.icon}
                                        <span className="flex-1">{item.title}</span>
                                        {item.badge ? (
                                            <Badge
                                                variant={isActive ? "secondary" : "default"}
                                                className={cn(
                                                    "rounded-full px-2 py-0 text-[10px]",
                                                    isActive && "bg-primary-foreground/15 text-primary-foreground",
                                                )}
                                            >
                                                {Number(item.badge) > 99 ? "99+" : item.badge}
                                            </Badge>
                                        ) : null}
                                    </Link>
                                );
                            })}

                            <Link
                                href="/notifications"
                                onClick={() => setMobileMenuOpen(false)}
                                className={cn(
                                    "mt-2 flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition-all duration-200",
                                    pathname === "/notifications"
                                        ? "bg-primary text-primary-foreground shadow-sm"
                                        : "text-foreground/75 hover:bg-muted hover:text-foreground",
                                )}
                            >
                                <Bell className="h-4 w-4" />
                                <span className="flex-1">Notifications</span>
                            </Link>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </>
    );
}
