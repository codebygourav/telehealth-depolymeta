"use client";

import {
    Avatar,
    AvatarFallback,
    AvatarImage,
    Button,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui";
import type { User } from "@/types/user-context";
import { LogOut, User as UserIcon } from "lucide-react";
import Link from "next/link";

interface HeaderUserProfileMenuProps {
    user: User | null | undefined;
    initializing: boolean;
    name: string;
    onLogout: () => Promise<void>;
}

export function HeaderUserProfileMenu({
    user,
    initializing,
    name,
    onLogout,
}: HeaderUserProfileMenuProps) {

    console.log("new user profile" , user);
    

    return (
        <div className="flex items-center gap-3 justify-end">
            <div className="hidden text-right md:flex md:flex-col">
                <span className="truncate text-sm font-semibold text-foreground">
                    {initializing ? "Loading user" : name}
                </span>
                <span className="truncate text-xs text-muted-foreground">
                    {initializing ? "Loading email" : user?.email || "patient@telehealth.test"}
                </span>
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        className="h-10 w-10 rounded-full p-0 hover:bg-transparent"
                        disabled={initializing}
                    >
                        <Avatar className="h-10 w-10 border border-border/70">
                            <AvatarImage src={user?.avatar || ""} alt={name} />
                            <AvatarFallback className="bg-primary/10 text-primary">
                                <UserIcon className="h-4 w-4" />
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" className="w-56  p-2">
                    <DropdownMenuGroup>
                        <DropdownMenuItem asChild className="cursor-pointer ">
                            <Link href="/profile">
                                <UserIcon className="mr-2 h-4 w-4" />
                                <span>Profile</span>
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        className="cursor-pointer  text-destructive focus:text-destructive"
                        onClick={onLogout}
                        disabled={initializing}
                    >
                        <LogOut className="mr-2 h-4 w-4" />
                        <span>Log out</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
