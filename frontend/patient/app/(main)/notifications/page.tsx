"use client";

import { useNotifications, useUnreadCount } from "@/queries/useNotifications";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Bell, Loader2, CircleCheck } from "lucide-react";
import { useState, useEffect } from "react";
import CustomTabs from "@/components/custom/CustomTabs";
import { fetchNotifications, markAllAsRead, markNotificationAsRead } from "@/api/notifications";
import { useQueryClient } from "@tanstack/react-query";
import { NotificationCard } from "@/components/pages/notification/NotificationCard";


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
    const { data, isLoading, error } = useNotifications() as {
        data: NotificationsResponse | undefined;
        isLoading: boolean;
        error: any;
    };
    const [activeTab, setActiveTab] = useState("all");
    const [currentPage, setCurrentPage] = useState(1);
    const [loadingMore, setLoadingMore] = useState(false);
    const [allNotifications, setAllNotifications] = useState<Notification[]>([]);
    const [totalPages, setTotalPages] = useState(1);
    const { data: totalUnread = 0 } = useUnreadCount();
    const queryClient = useQueryClient();

    const handleMarkAsRead = async (notificationId: string | number) => {
        try {
            await markNotificationAsRead(Number(notificationId));

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
            setCurrentPage(data?.pagination?.current_page || 1);
        }
    }, [data]);

    if (isLoading && currentPage === 1) {
        return (
            <div className="flex items-center justify-center min-h-100">
                <div className="animate-pulse text-muted-foreground">Loading notifications...</div>
            </div>
        );
    }

    if (error && currentPage === 1) {
        return (
            <div className="flex items-center justify-center min-h-100">
                <div className="text-red-500">Error loading notifications. Please try again.</div>
            </div>
        );
    }

    const hasMorePages = currentPage < totalPages;
    const filteredNotifications = activeTab === "unread" ? allNotifications.filter(item => !item.is_read) : allNotifications;

    const loadMore = async () => {
        if (loadingMore || !hasMorePages) return;
        setLoadingMore(true);
        try {
            const newData = await fetchNotifications(currentPage + 1);
            if (newData?.data?.length > 0) {
                setAllNotifications(prev => [...prev, ...newData.data]);
                setCurrentPage(prev => prev + 1);
            }
        } catch (error) {
            console.error("Error loading more notifications:", error);
        } finally {
            setLoadingMore(false);
        }
    };

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

    const renderNotifications = () => {
        if (filteredNotifications.length === 0) {
            return (
                <Card className="border-dashed">
                    <CardContent className="flex flex-col items-center justify-center py-12">
                        <Bell className="h-12 w-12 text-muted-foreground mb-3 opacity-50" />
                        <p className="text-muted-foreground text-center">
                            No {activeTab === "unread" ? "unread" : ""} notifications
                        </p>
                        <p className="text-sm text-muted-foreground text-center">
                            {activeTab === "all" ? "You're all caught up!" : "Check back later for updates."}
                        </p>
                    </CardContent>
                </Card>
            );
        }

        return (
            <div className="space-y-3 mt-5">
                {filteredNotifications.map(notification => (
                    <NotificationCard key={notification.id} notification={notification} onMarkAsRead={handleMarkAsRead} />
                ))}

                {hasMorePages && (
                    <div className="mt-8 text-center">
                        <Button variant="outline" size="sm" className="text-primary text-lg gap-2 h-12 w-auto px-5" onClick={loadMore} disabled={loadingMore}>
                            {loadingMore ? <><Loader2 className="h-4 w-4 animate-spin" /> Loading...</> : "Load more Notifications"}
                        </Button>
                    </div>
                )}

                {!hasMorePages && filteredNotifications.length > 0 && (
                    <div className="mt-6 text-center">
                        <p className="text-xs text-muted-foreground">You've seen all {filteredNotifications.length} notifications</p>
                    </div>
                )}
            </div>
        );
    };

    const tabsWithContent = tabs.map(tab => ({ ...tab, content: renderNotifications() }));

    return (
        <div className="w-full mx-auto">
            <div className="mb-6">
                <h1 className="text-4xl font-bold text-primary mb-2">Notifications</h1>
                <p className="text-muted-foreground">
                    Stay updated on your health journey. Here you'll find reminders, test results, and messages from your clinical team.
                </p>
            </div>

            <CustomTabs
                variant="pill"
                activeTabBg="#013220"
                activeTabColor="white"
                tabs={tabsWithContent}
                defaultTab="all"
                activeTab={activeTab}
                onTabChange={setActiveTab}
                tabsListClassName="w-full max-w-md!"
                rightSlot={
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
                }
            />
        </div>
    );
}