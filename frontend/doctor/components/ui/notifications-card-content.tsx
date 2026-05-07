"use client";

import { CardContent } from "@/components/ui/card";
import { Bell } from "lucide-react";
import { formatDistanceToNow } from "date-fns";
import { Button } from "./button";

interface NotificationItem {
    id: string;
    title: string;
    desc: string;
    created_at: string;
    is_read: boolean;
    group: string;
}

interface NotificationsCardContentProps {
    notifications: NotificationItem[];
    loading?: boolean;
    error?: string | null;

    limit?: number; // default 3
    onClickItem?: (id: string) => void;
    onViewAll?: () => void;

    getIcon?: (group: string) => React.ReactNode;

    emptyTitle?: string;
    emptyMessage?: string;
}

export default function NotificationsCardContent({
    notifications = [],
    loading = false,
    error = null,
    limit = 3,
    onClickItem,
    onViewAll,
    getIcon,
    emptyTitle = "No Notifications",
    emptyMessage = "You don't have any notifications at the moment.",
}: NotificationsCardContentProps) {
    const visibleNotifications = notifications.slice(0, limit);

    return (
        <CardContent className="flex flex-col flex-1 justify-between p-0">

            {/* 🔄 Loading */}
            {loading ? (
                <div className="flex items-center justify-center py-8 sm:py-12">
                    <div className="h-6 w-6 sm:h-8 sm:w-8 animate-spin rounded-full border-b-2 border-primary" />
                </div>
            ) : error ? (

                /* ❌ Error */
                <div className="py-8 sm:py-12 text-center text-xs sm:text-sm text-destructive px-4">
                    {error}
                </div>
            ) : notifications.length === 0 ? (

                /* 📭 Empty */
                <div className="flex flex-col items-center justify-center py-8 sm:py-12 px-4 text-center">
                    <Bell size={32} className="sm:size-40 text-muted-foreground mb-2 sm:mb-3" />
                    <p className="text-sm sm:text-base font-medium">{emptyTitle}</p>
                    <p className="text-xs sm:text-sm text-muted-foreground mt-1">{emptyMessage}</p>
                </div>

            ) : (

                /* ✅ Data */
                <div className="divide-y divide-border">
                    {visibleNotifications.map((notification) => (
                        <div
                            key={notification.id}
                            className={`flex gap-2 sm:gap-3 p-3 sm:p-4 cursor-pointer hover:bg-accent/50 transition-colors ${!notification.is_read ? "bg-accent/30" : ""
                                }`}
                            onClick={() => onClickItem?.(notification.id)}
                        >
                            {/* Icon */}
                            <div className="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-full bg-[#F5F6F8] shrink-0">
                                {getIcon?.(notification.group)}
                            </div>

                            {/* Content */}
                            <div className="flex-1 min-w-0">
                                <div className="flex items-start justify-between gap-2">

                                    <p className="text-[#1E1E1E] font-medium text-sm">
                                        {notification.title}
                                    </p>

                                    {!notification.is_read && (
                                        <span className="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-blue-500 shrink-0 mt-1" />
                                    )}
                                </div>

                                <p className="text-[#373737] text-xs line-clamp-2 mt-0.5">
                                    {notification.desc}
                                </p>

                                <p className="text-[10px] sm:text-xs text-muted-foreground/70 mt-1.5 sm:mt-2">
                                    {(() => {
                                        try {
                                            const date = new Date(notification.created_at);
                                            if (isNaN(date.getTime())) return "";
                                            return formatDistanceToNow(date, { addSuffix: true });
                                        } catch (e) {
                                            return "";
                                        }
                                    })()}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* 🔽 Bottom Action */}
            <div className="p-2 sm:p-3 border-t">
                <Button
                    className="w-full py-2.5 h-auto font-semibold"
                    onClick={onViewAll}
                >
                    View All Notifications
                </Button>
            </div>
        </CardContent>
    );
}