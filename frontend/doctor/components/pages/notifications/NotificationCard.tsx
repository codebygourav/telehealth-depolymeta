"use client";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
    Bell,
    Calendar,
    CheckCheck,
    Clock,
    FileText,
    Loader2,
    Star,
    Check,
    Eye,
    Pill,
    Shield,
    CreditCard,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { NotificationItem } from "@/types/notification";

const getNotificationIcon = (group: string) => {
    switch (group?.toLowerCase()) {
        case "appointment":
            return Calendar;
        case "review":
            return Star;
        case "availability":
            return Clock;
        case "document":
            return FileText;
        case "prescription":
            return Pill;
        case "vaccination":
            return Shield;
        case "payment":
            return CreditCard;
        default:
            return Bell;
    }
};

const getIconColor = (group: string) => {
    switch (group?.toLowerCase()) {
        case "appointment":
            return "text-blue-600";
        case "review":
            return "text-amber-600";
        case "availability":
            return "text-emerald-600";
        case "document":
            return "text-rose-600";
        case "prescription":
            return "text-green-600";
        case "vaccination":
            return "text-rose-600";
        case "payment":
            return "text-indigo-600";
        case "system":
            return "text-red-600";
        default:
            return "text-primary";
    }
};

const formatRelativeTime = (dateString: string) => {
    const now = new Date();
    const createdAt = new Date(dateString);

    const diffMs = now.getTime() - createdAt.getTime();
    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMinutes < 1) return "Just now";
    if (diffMinutes < 60) return `${diffMinutes}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays === 1) return "Yesterday";
    return `${diffDays} days ago`;
};

interface NotificationCardProps {
    notification: NotificationItem;
    onRead: (id: string) => void;
    isReading: boolean;
}

export function NotificationCard({
    notification,
    onRead,
    isReading,
}: NotificationCardProps) {
    const Icon = getNotificationIcon(notification.group);

    return (
        <Card
            className={cn(
                "border-border transition-colors hover:bg-accent/30",
                !notification.is_read ? "bg-primary/5 border-l-4 border-l-primary" : ""
            )}
        >
            <CardContent className="p-3 sm:p-4">
                <div className="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
                    {/* Icon - Left */}
                    <div className="flex items-start gap-3 sm:gap-4">
                        <div
                            className={cn(
                                "flex h-8 w-8 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-full bg-accent",
                                getIconColor(notification.group)
                            )}
                        >
                            <Icon className="h-4 w-4 sm:h-5 sm:w-5 text-primary" />
                        </div>

                        {/* Content - Middle */}
                        <div className="flex-1 min-w-0 sm:hidden">
                            <div className="flex items-start justify-between gap-2">
                                <p className="text-sm font-medium line-clamp-2">
                                    {notification.title}
                                </p>
                                {notification.is_read && (
                                    <Badge variant="secondary" className="text-[10px] px-1.5 shrink-0">
                                        Read
                                    </Badge>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground line-clamp-2 mt-1">
                                {notification.desc}
                            </p>
                            <p className="flex items-center gap-1 text-[10px] text-muted-foreground mt-1.5">
                                <Clock className="h-2.5 w-2.5" />
                                {notification.created_at}
                            </p>
                        </div>
                    </div>

                    {/* Desktop Layout */}
                    <div className="hidden sm:flex sm:flex-1 sm:items-start sm:justify-between sm:gap-4">
                        <div className="flex-1 min-w-0 space-y-1">
                            <div className="flex items-start justify-between gap-2">
                                <p className="font-medium">{notification.title}</p>
                                {notification.is_read && (
                                    <Badge variant="secondary" className="shrink-0">Read</Badge>
                                )}
                            </div>
                            <p className="text-sm text-muted-foreground line-clamp-2">
                                {notification.desc}
                            </p>
                            <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                <Clock className="h-3 w-3" />
                                {notification.created_at}
                            </p>
                        </div>

                        {/* Button - Right */}
                        <div className="shrink-0 pt-0.5">
                            {!notification.is_read && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => onRead(notification.id)}
                                    disabled={isReading}
                                    className="h-8 sm:h-9 text-xs sm:text-sm px-2 sm:px-3"
                                >
                                    {isReading ? (
                                        <Loader2 className="mr-1.5 sm:mr-2 h-3 w-3 sm:h-4 sm:w-4 animate-spin" />
                                    ) : (
                                        <Eye className="mr-1.5 sm:mr-2 h-3 w-3 sm:h-4 sm:w-4" />
                                    )}
                                    
                                    <span className="xs:hidden">Mark as read</span>
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Mobile Button - Below Content */}
                    <div className="sm:hidden">
                        {!notification.is_read && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => onRead(notification.id)}
                                disabled={isReading}
                                className="w-full mt-2 h-8 text-xs"
                            >
                                {isReading ? (
                                    <Loader2 className="mr-1.5 h-3 w-3 animate-spin" />
                                ) : (
                                    <Eye className="mr-1.5 h-3 w-3" />
                                )}
                                {isReading ? "Reading..." : "Mark as read"}
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}