"use client";
import { useState, useEffect } from "react";
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbSeparator,
    Button,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { useAuth } from "@/context/userContext";
import { cn } from "@/lib/utils";
import { useUnreadCount } from "@/queries/useNotifications";
import {
    Bell,
    LogOut,
    User as UserIcon,
    ChevronDown,
    Star,
    Banknote,
    LayoutDashboard,
    Calendar,
    ClipboardPlus,
    Pill,
    House,
    Search,
    Menu,
    X,

} from "lucide-react";
import logo from "@/public/assets/icon/logo-light.png";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { usePathname } from "next/navigation";
import Image from "next/image";

export function Header() {
    const [isScrolled, setIsScrolled] = useState(false);
    const [desktopSearchOpen, setDesktopSearchOpen] = useState(false);
    const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
    const [desktopSearchValue, setDesktopSearchValue] = useState("");
    const [mobileSearchValue, setMobileSearchValue] = useState("");
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

        const handleResize = () => {
            if (window.innerWidth >= 1024) {
                setMobileMenuOpen(false);
            }
        };
        window.addEventListener("resize", handleResize);

        return () => {
            window.removeEventListener("scroll", handleScroll);
            window.removeEventListener("resize", handleResize);
        };
    }, []);

    useEffect(() => {
        setMobileMenuOpen(false);
    }, [pathname]);

    const name =
        user && (user.first_name || user.last_name)
            ? `${user.role === "doctor" ? "Dr. " : ""}${user.first_name ?? ""} ${user.last_name ?? ""}`.trim()
            : "User";

    const searchItems = [
        { label: "Dashboard", icon: LayoutDashboard, href: "/" },
        { label: "Find Doctors", icon: Calendar, href: "/find-doctors" },
        { label: "My Appointments", icon: UserIcon, href: "/appointments" },
        { label: "Medical Records", icon: ClipboardPlus, href: "/medical-records" },
        { label: "My Medicines", icon: Pill, href: "/my-medicines" },
        { label: "My Profile", icon: UserIcon, href: "/profile" },
        { label: "My Reviews", icon: Star, href: "/reviews" },
        { label: "Transactions", icon: Banknote, href: "/transactions" },
        { label: "Notifications", icon: Bell, href: "/notifications" },
    ];

    const handleSearch = (href: string, isDesktop: boolean = true) => {
        router.push(href);
        if (isDesktop) {
            setDesktopSearchOpen(false);
            setDesktopSearchValue("");
        } else {
            setMobileSearchOpen(false);
            setMobileSearchValue("");
        }
    };

    const handleLogout = async () => {
        await logout();
        router.push("/auth/login");
    };

    // Filter search items based on search value
    const desktopFilteredItems = searchItems.filter(item =>
        item.label.toLowerCase().includes(desktopSearchValue.toLowerCase())
    );

    const mobileFilteredItems = searchItems.filter(item =>
        item.label.toLowerCase().includes(mobileSearchValue.toLowerCase())
    );

    return (
        <>
            <header
                className={cn(
                    "sticky top-0  w-full border-b transition-all duration-300",
                    isScrolled
                        ? "bg-background/95 backdrop-blur-md supports-backdrop-filter:bg-background/60 shadow-lg"
                        : "bg-background border-border shadow-sm",
                )}
            >
                <div className="flex h-16 items-center justify-between px-5 container mx-auto gap-2 md:gap-4">

                    {/* Left Section */}
                    <div className="flex items-center gap-2 md:gap-3 lg:gap-4 min-w-0">


                        <div className="lg:hidden flex items-center">
                            <Image
                                src={logo}
                                alt="Telehealth"
                                className="w-32 h-9 object-contain"
                                priority
                            />
                        </div>

                        <div className="hidden lg:block">
                            <h1 className="text-base font-bold mb-0.5">
                                Patient Dashboard
                            </h1>
                            <Breadcrumb>
                                <BreadcrumbList className="flex-wrap">
                                    <BreadcrumbItem>
                                        <House className="h-3.5 w-3.5 text-muted-foreground" />
                                    </BreadcrumbItem>

                                    <span className="text-muted-foreground text-xs">/</span>

                                    <BreadcrumbItem>
                                        <BreadcrumbLink
                                            href="/"
                                            className={cn(
                                                "text-xs transition-colors",
                                                pathname === "/"
                                                    ? "text-primary font-semibold"
                                                    : "text-muted-foreground hover:text-foreground"
                                            )}
                                        >
                                            Dashboard
                                        </BreadcrumbLink>
                                    </BreadcrumbItem>

                                    <span className="text-muted-foreground text-xs">/</span>

                                    <BreadcrumbItem>
                                        <BreadcrumbLink
                                            href="/patient"
                                            className={cn(
                                                "text-xs transition-colors",
                                                pathname === "/patient"
                                                    ? "text-primary font-semibold"
                                                    : "text-muted-foreground hover:text-foreground"
                                            )}
                                        >
                                            Patient
                                        </BreadcrumbLink>
                                    </BreadcrumbItem>
                                </BreadcrumbList>
                            </Breadcrumb>
                        </div>


                    </div>

                    {/* Right Section */}
                    <div className="flex items-center gap-1.5 sm:gap-2 lg:gap-3 shrink-0">

                        {/* Desktop Search */}
                        <div className="hidden md:block">
                            <Popover open={desktopSearchOpen} onOpenChange={setDesktopSearchOpen}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className="w-48 lg:w-64 xl:w-80 justify-between text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Search className="w-4 h-4" />
                                            <span className="text-sm">Search pages...</span>
                                        </div>
                                        <kbd className="hidden sm:inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground">
                                            ⌘K
                                        </kbd>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="p-0 w-80 md:w-96" align="end">
                                    <Command>
                                        <CommandInput
                                            placeholder="Search pages..."
                                            value={desktopSearchValue}
                                            onValueChange={setDesktopSearchValue}
                                        />
                                        <CommandList>
                                            <CommandEmpty>No results found.</CommandEmpty>
                                            <CommandGroup heading="Pages">
                                                {desktopFilteredItems.map((item) => (
                                                    <CommandItem
                                                        key={item.href}
                                                        onSelect={() => handleSearch(item.href, true)}
                                                        className="cursor-pointer"
                                                    >
                                                        <item.icon className="mr-2 h-4 w-4" />
                                                        <span>{item.label}</span>
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                        </div>

                        {/* Mobile Search Button */}
                        <div className="md:hidden">
                            <Popover open={mobileSearchOpen} onOpenChange={setMobileSearchOpen}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8 sm:h-9 sm:w-9"
                                    >
                                        <Search className="h-4 w-4 sm:h-5 sm:w-5" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    className="p-0 w-[calc(100vw-2rem)] max-w-md mx-4"
                                    align="end"
                                    sideOffset={8}
                                >
                                    <Command>
                                        <CommandInput
                                            placeholder="Search pages..."
                                            value={mobileSearchValue}
                                            onValueChange={setMobileSearchValue}
                                            autoFocus
                                        />
                                        <CommandList>
                                            <CommandEmpty>No results found.</CommandEmpty>
                                            <CommandGroup heading="Pages">
                                                {mobileFilteredItems.map((item) => (
                                                    <CommandItem
                                                        key={item.href}
                                                        onSelect={() => handleSearch(item.href, false)}
                                                        className="cursor-pointer"
                                                    >
                                                        <item.icon className="mr-2 h-4 w-4" />
                                                        <span>{item.label}</span>
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                        </div>

                        {/* Notification Bell */}
                        <Link href="/notifications">
                            <div className="relative">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="relative h-8 w-8 sm:h-9 sm:w-9 hover:bg-accent"
                                >
                                    <Bell className="h-4 w-4 sm:h-5 sm:w-5" />
                                </Button>
                                {unreadData > 0 && (
                                    <span className="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] sm:text-[11px] font-semibold px-1.5 py-0.5 rounded-full min-w-4.5 text-center leading-none shadow-lg">
                                        {unreadData > 99 ? "99+" : unreadData}
                                    </span>
                                )}
                            </div>
                        </Link>

                        <Button
                            variant="ghost"
                            size="icon"
                            className="lg:hidden shrink-0"
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                        >
                            <Menu className="h-4 w-4 sm:h-5 sm:w-5" />
                        </Button>

                        {/* User Section */}
                        <div className="md:flex hidden items-center gap-2">
                            <div className="hidden sm:flex flex-col items-end">
                                <span className="text-sm font-semibold text-foreground truncate max-w-30 md:max-w-37.5">
                                    {name}
                                </span>
                                <span className="text-xs text-muted-foreground truncate max-w-30 md:max-w-37.5">
                                    {user?.email}
                                </span>
                            </div>

                            {user && !initializing ? (
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            className="md:flex hidden items-center gap-1 sm:gap-2 h-8 sm:h-10 px-1.5 sm:px-2 rounded-full bg-muted/50 hover:bg-muted border border-border/50 transition-all shrink-0"
                                        >
                                            <Avatar className="h-7 w-7 sm:h-8 sm:w-8 border-2 border-background">
                                                <AvatarImage src={user?.avatar || ""} alt={name} />
                                                <AvatarFallback className="bg-primary/10">
                                                    <UserIcon className="h-3.5 w-3.5 sm:h-4 sm:w-4 text-primary" />
                                                </AvatarFallback>
                                            </Avatar>
                                            <ChevronDown className="h-3 w-3 sm:h-4 sm:w-4 text-muted-foreground hidden sm:block" />
                                        </Button>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent
                                        className="w-56 rounded-xl p-2 shadow-lg"
                                        align="end"
                                        sideOffset={8}
                                    >
                                        <DropdownMenuGroup>
                                            <DropdownMenuItem asChild className="rounded-md cursor-pointer py-2.5">
                                                <Link href="/profile" className="flex items-center">
                                                    <UserIcon className="mr-3 h-4 w-4 text-muted-foreground" />
                                                    <span className="font-medium">My Profile</span>
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild className="rounded-md cursor-pointer py-2.5">
                                                <Link href="/reviews" className="flex items-center">
                                                    <Star className="mr-3 h-4 w-4 text-muted-foreground" />
                                                    <span className="font-medium">My Reviews</span>
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild className="rounded-md cursor-pointer py-2.5">
                                                <Link href="/transactions" className="flex items-center">
                                                    <Banknote className="mr-3 h-4 w-4 text-muted-foreground" />
                                                    <span className="font-medium">Transactions</span>
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuGroup>

                                        <DropdownMenuSeparator className="my-2" />

                                        <DropdownMenuItem
                                            className="rounded-md cursor-pointer py-2.5 text-destructive focus:text-destructive focus:bg-destructive/10"
                                            onClick={handleLogout}
                                        >
                                            <LogOut className="mr-3 h-4 w-4" />
                                            <span className="font-semibold">Logout</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            ) : (
                                !initializing && (
                                    <Link
                                        href="/auth/login"
                                        className="text-sm font-medium text-primary hover:underline px-2 sm:px-3 py-2 whitespace-nowrap transition-colors"
                                    >
                                        Sign In
                                    </Link>
                                )
                            )}
                        </div>
                    </div>
                </div>
            </header>

            {/* Mobile Menu Sidebar */}
            <div
                className={cn(
                    "fixed inset-0 bg-black/50 z-40 lg:hidden transition-all duration-300",
                    mobileMenuOpen ? "opacity-100 visible" : "opacity-0 invisible"
                )}
                onClick={() => setMobileMenuOpen(false)}
            />

            <div
                className={cn(
                    "fixed right-0 top-0 h-full w-90 bg-background z-50 lg:hidden shadow-2xl transition-transform duration-300 ease-in-out",
                    mobileMenuOpen ? "translate-x-0" : "translate-x-full"
                )}
            >
                {/* <div className="flex items-center justify-between p-4 border-b">
                    <h2 className="font-bold text-lg">Menu</h2>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setMobileMenuOpen(false)}
                        className="h-8 w-8"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                </div> */}

                <div className="p-2 space-y-1">
                    {user && !initializing && (
                        <div className="flex justify-between items-center px-3 py-4 mb-2 border-b">
                            <div className="flex items-center gap-3">
                                <Avatar className="h-10 w-10">
                                    <AvatarImage src={user?.avatar || ""} alt={name} />
                                    <AvatarFallback className="bg-primary/10">
                                        <UserIcon className="h-5 w-5 text-primary" />
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p className="font-semibold text-sm">{name}</p>
                                    <p className="text-xs text-muted-foreground">{user?.email}</p>
                                </div>
                            </div>

                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => setMobileMenuOpen(false)}
                                className="h-8 w-8"
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        </div>
                    )}

                    {searchItems.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            onClick={() => setMobileMenuOpen(false)}
                            className={cn(
                                "flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors",
                                pathname === item.href
                                    ? "bg-primary/10 text-primary"
                                    : "hover:bg-muted"
                            )}
                        >
                            <item.icon className={cn(
                                "h-4 w-4",
                                pathname === item.href ? "text-primary" : "text-muted-foreground"
                            )} />
                            <span className="text-sm font-medium">{item.label}</span>
                        </Link>
                    ))}

                    {user && !initializing && (
                        <>
                            <div className="border-t my-2" />
                            <button
                                onClick={() => {
                                    handleLogout();
                                    setMobileMenuOpen(false);
                                }}
                                className="flex items-center gap-3 px-3 py-2.5 rounded-md hover:bg-destructive/10 text-destructive w-full transition-colors"
                            >
                                <LogOut className="h-4 w-4" />
                                <span className="text-sm font-medium">Logout</span>
                            </button>
                        </>
                    )}
                </div>
            </div>
        </>
    );
}