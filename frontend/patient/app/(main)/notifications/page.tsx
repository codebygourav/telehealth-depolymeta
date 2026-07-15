"use client";

import { markAllAsRead, markNotificationAsRead } from "@/api/notifications";
import { CustomPagination } from "@/components/custom/CustomPagination";
import CustomTabs from "@/components/custom/CustomTabs";
import { EmptyState } from "@/components/custom/EmptyState";
import HeroSection from "@/components/hero-section";
import { NotificationCard } from "@/components/pages/notification/NotificationCard";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useNotifications, useUnreadCount } from "@/queries/useNotifications";
import { useQueryClient } from "@tanstack/react-query";
import { CircleCheck } from "lucide-react";
import { useEffect, useState } from "react";
import { usePushNotifications } from "@/hooks/usePushNotifications";
import { Bell, BellOff, Loader2 } from "lucide-react";



interface Notification {
    id: string | number;
    title: string;
    desc: string;
    group: string;
    is_read: boolean;
    created_at: string;
}

interface NotificationsResponse {
    data: Notification[];
    pagination?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function Notifications() {
    const [currentPage, setCurrentPage] = useState(1);
    const { data, isLoading, error } = useNotifications(currentPage) as {
        data: NotificationsResponse | undefined;
        isLoading: boolean;
        error: any;
    };
    const [activeTab, setActiveTab] = useState("all");
    const [allNotifications, setAllNotifications] = useState<Notification[]>([]);
    const [totalPages, setTotalPages] = useState(1);
    const [mounted, setMounted] = useState(false);
    
    useEffect(() => {
        setMounted(true);
    }, []);
    const { data: totalUnread = 0 } = useUnreadCount();
    const queryClient = useQueryClient();
    const {
        subscription,
        loading: pushLoading,
        subscribeToPush,
        unsubscribeFromPush,
        isSupported
    } = usePushNotifications();

    const handleMarkAsRead = async (notificationId: string | number) => {
        try {
            await markNotificationAsRead(String(notificationId));

            setAllNotifications(prev =>
                prev.map(n =>
                    String(n.id) === String(notificationId) ? { ...n, is_read: true } : n
                )
            );

            queryClient.setQueryData(["unread-count"], (old: number = 0) => old > 0 ? old - 1 : 0);
        } catch (error) {
            console.error("Failed to mark notification as read", error);
        }
    };

    const handleMarkAllRead = async () => {
        try {
            const res = await markAllAsRead();
            const unread = res?.data?.unread_count ?? 0;
            queryClient.setQueryData(["unread-count"], unread);
            setAllNotifications(prev => prev.map(item => ({ ...item, is_read: true })));
            queryClient.invalidateQueries({ queryKey: ["notifications"] });
        } catch (error) {
            console.error("Failed to mark all as read", error);
        }
    };

    useEffect(() => {
        if (data?.data) {
            setAllNotifications(data.data);
            setTotalPages(data?.pagination?.last_page || 1);
        }
    }, [data]);

    useEffect(() => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }, [currentPage]);

    const filteredNotifications = activeTab === "unread" ? allNotifications.filter(item => !item.is_read) : allNotifications;

    const tabs = [
        { key: "all", label: <div className="flex items-center gap-1">All</div> },
        {
            key: "unread",
            label: (
                <div className="flex items-center gap-1">
                    Unread
                    {totalUnread > 0 && <Badge variant="secondary" className="ml-2 bg-muted">{totalUnread}</Badge>}
                </div>
            )
        }
    ];

    // Move all UI rendering logic to here, and do not use early return statements inside Notifications.
    let content = null;

    if (isLoading && currentPage === 1) {
        content = (
            <div className="flex items-center justify-center min-h-100">
                <div className="animate-pulse text-muted-foreground">Loading notifications...</div>
            </div>
        );
    } else if (error && currentPage === 1) {
        content = (
            <div className="flex items-center justify-center min-h-100">
                <div className="text-red-500">Error loading notifications. Please try again.</div>
            </div>
        );
    } else {
        content = (
            <div className="space-y-5 mt-5">
                {filteredNotifications.length === 0 ? (
                    <EmptyState
                        title="No notifications found"
                        description="You're all caught up!"
                    />
                ) : (
                    filteredNotifications.map(notification => (
                        <NotificationCard key={notification.id} notification={notification} onMarkAsRead={handleMarkAsRead} />
                    ))
                )}

                <div className="flex flex-col items-center gap-4 py-8">
                    <CustomPagination
                        currentPage={currentPage}
                        totalPages={totalPages}
                        onPageChange={setCurrentPage}
                    />
                    {data?.pagination && (
                        <p className="text-xs text-muted-foreground font-medium">
                            Showing <span className="text-foreground">{(currentPage - 1) * (data.pagination.per_page || 10) + 1}</span> to{" "}
                            <span className="text-foreground">{Math.min(currentPage * (data.pagination.per_page || 10), data.pagination.total)}</span> of{" "}
                            <span className="text-foreground">{data.pagination.total} </span> notifications
                        </p>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div className="container-max-width w-full mx-auto">
            <HeroSection
                title="All Notification"
                description="See all your notification here"
            />
            <CustomTabs
                variant="pill"
                activeTabBg="#013220"
                activeTabColor="white"
                tabs={tabs}
                defaultTab="all"
                activeTab={activeTab}
                onTabChange={setActiveTab}
                tabsListClassName="w-full max-w-md! mt-1"
                rightSlot={
                    <div className="flex items-center gap-3 flex-wrap">
                        {mounted && isSupported && (
                            <Button
                                variant="outline"
                                size="sm"
                                className="h-10 px-3 text-sm flex items-center gap-1.5 whitespace-nowrap shrink-0 rounded-xl"
                                onClick={subscription ? unsubscribeFromPush : subscribeToPush}
                                disabled={pushLoading}
                            >
                                {pushLoading ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : subscription ? (
                                    <>
                                        <BellOff className="h-4 w-4 text-red-500" />
                                        Disable Desktop Push
                                    </>
                                ) : (
                                    <>
                                        <Bell className="h-4 w-4 text-primary" />
                                        Enable Desktop Push
                                    </>
                                )}
                            </Button>
                        )}
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-10 px-2 text-sm text-primary bg-transparent shadow-none hover:bg-transparent underline-offset-4 hover:underline decoration-primary flex items-center gap-1 whitespace-nowrap shrink-0"
                            onClick={handleMarkAllRead}
                            disabled={totalUnread === 0}
                        >
                            <CircleCheck className="h-4 w-4" />
                            Mark all as read
                        </Button>
                    </div>
                }
            />
            {content}
        </div>
    );
}