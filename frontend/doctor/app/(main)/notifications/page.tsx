"use client";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Bell,
  BellOff,
  Calendar,
  CheckCheck,
  Loader2,
  Star,
} from "lucide-react";
import { useMemo, useState } from "react";

import CustomTabs, { TabItem } from "@/components/custom/CustomTabs";
import { cn } from "@/lib/utils";
import { useNotifications, useReadNotification } from "@/queries/notifications";
import { NotificationItem } from "@/types/notification";

import { NotificationCard } from "@/components/pages/notifications/NotificationCard";

const isToday = (dateString: string) => {
  const date = new Date(dateString);
  const today = new Date();

  return (
    date.getDate() === today.getDate() &&
    date.getMonth() === today.getMonth() &&
    date.getFullYear() === today.getFullYear()
  );
};

const EmptyState = ({
  icon: Icon,
  title,
  desc,
}: {
  icon: any;
  title: string;
  desc: string;
}) => (
  <div className="flex flex-col items-center justify-center py-32 text-center animate-in fade-in slide-in-from-bottom-8 duration-1000 ease-out">
    <div className="relative mb-8">
      <div className="absolute inset-x-0 -top-4 -bottom-4 scale-150 blur-3xl opacity-20 bg-primary/30 rounded-full" />
      <div className="relative flex h-24 w-24 items-center justify-center rounded-[2.5rem] bg-gradient-to-br from-card to-accent/30 border border-border/50 shadow-2xl">
        <Icon className="h-10 w-10 text-muted-foreground/30" />
      </div>
    </div>
    <h3 className="mb-3 text-2xl font-black tracking-tight text-foreground">
      {title}
    </h3>
    <p className="max-w-[320px] text-[15px] leading-relaxed text-muted-foreground/50">
      {desc}
    </p>
  </div>
);

export default function NotificationsPage() {
  const { data, isLoading, isError, error, refetch } = useNotifications();
  const readNotificationMutation = useReadNotification();

  const [readingId, setReadingId] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState("all");

  const notifications = data?.data ?? [];
  const unreadCount = data?.meta?.total_unread ?? 0;

  const unreadNotifications = useMemo(() => {
    return notifications.filter((item) => !item.is_read);
  }, [notifications]);

  const appointmentNotifications = useMemo(() => {
    return notifications.filter((item) => item.group === "appointment");
  }, [notifications]);

  const reviewNotifications = useMemo(() => {
    return notifications.filter((item) => item.group === "review");
  }, [notifications]);

  const todayNotifications = useMemo(() => {
    return notifications.filter((item) => isToday(item.created_at));
  }, [notifications]);

  const earlierNotifications = useMemo(() => {
    return notifications.filter((item) => !isToday(item.created_at));
  }, [notifications]);

  const handleReadNotification = async (notificationId: string) => {
    setReadingId(notificationId);
    try {
      await readNotificationMutation.mutateAsync(notificationId);
    } finally {
      setReadingId(null);
    }
  };

  const renderNotificationList = (
    list: NotificationItem[],
    emptyConfig: { icon: any; title: string; desc: string },
  ) => {
    if (list.length === 0) {
      return <EmptyState {...emptyConfig} />;
    }

    return (
      <div className="space-y-4 pt-6">
        {list.map((notification) => (
          <NotificationCard
            key={notification.id}
            notification={notification}
            onRead={handleReadNotification}
            isReading={readingId === notification.id}
          />
        ))}
      </div>
    );
  };

  const allTabContent = useMemo(() => {
    if (notifications.length === 0) {
      return (
        <EmptyState
          icon={BellOff}
          title="No notifications yet"
          desc="We'll notify you when something important happens."
        />
      );
    }

    return (
      <div className="space-y-10 pt-4">
        {todayNotifications.length > 0 && (
          <div className="space-y-5">
            <div className="flex items-center gap-4 px-1">
              <h3 className="text-[11px] font-bold uppercase tracking-[0.2em] text-primary">
                Today
              </h3>
              <div className="h-px flex-1 bg-gradient-to-r from-primary/20 to-transparent" />
            </div>
            <div className="space-y-4">
              {todayNotifications.map((notification) => (
                <NotificationCard
                  key={notification.id}
                  notification={notification}
                  onRead={handleReadNotification}
                  isReading={readingId === notification.id}
                />
              ))}
            </div>
          </div>
        )}

        {earlierNotifications.length > 0 && (
          <div className="space-y-5">
            <div className="flex items-center gap-4 px-1">
              <h3 className="text-[11px] font-bold uppercase tracking-[0.2em] text-muted-foreground/60">
                Earlier
              </h3>
              <div className="h-px flex-1 bg-gradient-to-r from-border/60 to-transparent" />
            </div>
            <div className="space-y-4">
              {earlierNotifications.map((notification) => (
                <NotificationCard
                  key={notification.id}
                  notification={notification}
                  onRead={handleReadNotification}
                  isReading={readingId === notification.id}
                />
              ))}
            </div>
          </div>
        )}
      </div>
    );
  }, [notifications, todayNotifications, earlierNotifications, readingId]);

  const tabs: TabItem[] = [
    {
      key: "all",
      label: (
        <div className="flex items-center gap-2">
          <span>All</span>
          {unreadCount > 0 && (
            <Badge
              variant="secondary"
              className={cn(
                "rounded-full px-1.5 py-0 text-[10px] font-bold transition-all",
                activeTab === "all"
                  ? "bg-primary text-primary-foreground"
                  : "bg-muted text-muted-foreground",
              )}
            >
              {unreadCount}
            </Badge>
          )}
        </div>
      ),
      content: allTabContent,
    },
    {
      key: "unread",
      label: "Unread",
      content: renderNotificationList(unreadNotifications, {
        icon: CheckCheck,
        title: "All caught up!",
        desc: "You've read all your notifications.",
      }),
    },
    {
      key: "appointments",
      label: "Appointments",
      content: renderNotificationList(appointmentNotifications, {
        icon: Calendar,
        title: "No appointments",
        desc: "No appointment-related notifications found.",
      }),
    },
    {
      key: "reviews",
      label: "Reviews",
      content: renderNotificationList(reviewNotifications, {
        icon: Star,
        title: "No reviews",
        desc: "No review-related notifications found.",
      }),
    },
  ];

  if (isLoading) {
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center space-y-6">
        <div className="relative">
          <Loader2 className="h-12 w-12 animate-spin text-primary/40" />
          <Bell className="absolute left-1/2 top-1/2 h-5 w-5 -translate-x-1/2 -translate-y-1/2 text-primary" />
        </div>
        <p className="text-sm font-medium text-muted-foreground animate-pulse">
          Fetching your notifications...
        </p>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="flex flex-col items-center justify-center space-y-6 py-20 text-center">
        <div className="flex h-20 w-20 items-center justify-center rounded-full bg-destructive/10 text-destructive">
          <BellOff className="h-10 w-10" />
        </div>
        <div>
          <h1 className="text-xl font-bold">Failed to load notifications</h1>
          <p className="text-sm text-muted-foreground">
            {error instanceof Error ? error.message : "Something went wrong"}
          </p>
        </div>
        <Button
          onClick={() => refetch()}
          variant="outline"
          className="rounded-full px-8"
        >
          Try Again
        </Button>
      </div>
    );
  }

  return (
    <>
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 ">
        <div className="space-y-4">

          <div className="space-y-1">
            <h1 className="font-bold">
              Notifications
            </h1>
            <p className="text-muted-foreground/60 text-sm font-medium flex items-center gap-2">
              Real-time updates on your clinical activity.
              {unreadCount > 0 && (
                <span className="flex items-center gap-1.5 font-bold text-primary ml-1">
                  <span className="relative flex h-2 w-2">
                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                    <span className="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                  </span>
                  {unreadCount} unread
                </span>
              )}
            </p>
          </div>
        </div>
      </div>

      
      <CustomTabs
        tabs={tabs}
        activeTab={activeTab}
        onTabChange={setActiveTab}
        tabsListClassName="md:max-w-2xl w-full overflow-x-auto overflow-y-hidden flex-nowrap justify-start sm:justify-start md:justify-start lg:justify-start [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
      />
      
    </>
  );
}
