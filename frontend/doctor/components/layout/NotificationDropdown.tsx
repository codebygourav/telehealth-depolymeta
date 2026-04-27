"use client";

import {
  Badge,
  Button,
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  ScrollArea,
} from "@/components/ui";
import { cn } from "@/lib/utils";
import { useNotifications, useReadNotification } from "@/queries/notifications";
import {
  Bell,
  Calendar,
  ChevronDown,
  CheckCircle2,
  Clock,
  FileText,
  Loader2,
  Settings,
} from "lucide-react";
import Link from "next/link";
import { useState } from "react";

export function NotificationDropdown() {
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [readingId, setReadingId] = useState<string | null>(null);

  const { data: notificationsData, isLoading: isLoadingNotifications } = useNotifications();
  const markAsReadMutation = useReadNotification();
  const unreadCount = notificationsData?.meta?.total_unread ?? 0;
  const notifications = notificationsData?.data ?? [];

  const toggleExpand = (e: React.MouseEvent, id: string) => {
    e.preventDefault();
    e.stopPropagation();
    setExpandedId(expandedId === id ? null : id);
  };

  const handleNotificationClick = async (id: string) => {
    setReadingId(id);
    try {
      await markAsReadMutation.mutateAsync(id);
    } finally {
      setReadingId(null);
    }
  };

  const getNotificationTypeIcon = (group: string) => {
    switch (group?.toLowerCase()) {
      case "appointment":
        return <Calendar className="h-4 w-4" />;
      case "review":
        return <Settings className="h-4 w-4 text-amber-500" />;
      case "document":
        return <FileText className="h-4 w-4" />;
      case "availability":
        return <Clock className="h-4 w-4" />;
      default:
        return <Bell className="h-4 w-4 text-primary" />;
    }
  };

  const getNotificationTypeColor = (group: string) => {
    switch (group?.toLowerCase()) {
      case "appointment":
        return "text-blue-600";
      case "review":
        return "text-amber-500";
      case "document":
        return "text-rose-600";
      case "availability":
        return "text-emerald-600";
      default:
        return "text-primary";
    }
  };

  const formatNotificationTime = (date: string) => {
    const now = new Date();
    const then = new Date(date);
    const diff = now.getTime() - then.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (minutes < 1) return "just now";
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className="relative">
          <Bell className="h-6! w-6!"/>
          {unreadCount > 0 && (
            <span className="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] text-white">
              {unreadCount > 99 ? "99+" : unreadCount}
            </span>
          )}
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent
        align="end"
        className="w-[calc(100vw)] sm:w-96 p-0 overflow-hidden shadow-2xl border-border/50"
      >
        <div className="flex flex-col max-h-[550px] bg-background">
          {/* Header Section */}
          <div className="flex items-center justify-between p-4 border-b border-border/40">
            <div className="flex flex-col gap-0.5">
              <span className="text-sm font-bold text-foreground">Notifications</span>
              <span className="text-[10px] text-muted-foreground uppercase tracking-widest font-medium">Recent Alerts</span>
            </div>
            {unreadCount > 0 && (
              <Badge variant="secondary" className="bg-primary rounded-sm text-primary-foreground text-[11px] font-semibold h-5 px-2 animate-in fade-in zoom-in duration-300">
                {unreadCount} New
              </Badge>
            )}
          </div>

          {/* Items Section */}
          <ScrollArea className="flex-1 overflow-y-auto min-h-0 bg-accent/[0.02]">
            <div className="px-1 py-1">
              {isLoadingNotifications ? (
                <div className="flex flex-col items-center justify-center p-16 space-y-4">
                  <Loader2 className="h-6 w-6 animate-spin text-primary/40" />
                  <p className="text-[11px] text-muted-foreground font-bold uppercase tracking-[0.2em]">Syncing Feed</p>
                </div>
              ) : notifications.length > 0 ? (
                <div className="flex flex-col">
                  {notifications.slice(0, 5).map((notification) => (
                    <div
                      key={notification.id}
                      className={cn(
                        "flex flex-col p-3 border-b border-border/60 last:border-0 transition-all duration-300 cursor-pointer group",
                        !notification.is_read ? "bg-primary/[0.04]" : "hover:bg-accent/40",
                        expandedId === notification.id && "bg-accent/20"
                      )}
                      onClick={(e) => toggleExpand(e, notification.id)}
                    >
                      <div className="flex flex-col w-full">
                        {/* Header Row */}
                        <div className="flex items-center gap-3 w-full">
                          <div className={cn(
                            "flex h-9 w-9 shrink-0 items-center justify-center rounded-md transition-all relative border border-border/10 shadow-sm",
                            notification.group === "appointment" && "bg-blue-100/50 text-blue-600",
                            notification.group === "review" && "bg-amber-100/50 text-amber-500",
                            notification.group === "availability" && "bg-emerald-100/50 text-emerald-600",
                            notification.group === "document" && "bg-rose-100/50 text-rose-600",
                            notification.group && "bg-primary/10 text-primary",
                          )}>
                            {getNotificationTypeIcon(notification.group)}
                            {!notification.is_read && (
                              <div className="absolute -top-1 -right-1 h-3 w-3 rounded-full bg-primary border-2 border-background ring-2 ring-primary/5 shadow-sm" />
                            )}
                          </div>

                          <div className="flex items-center justify-between flex-1 min-w-0">
                            <span className={cn(
                              "text-[13px] truncate transition-colors",
                              !notification.is_read ? "text-foreground" : ""
                            )}>
                              {notification.title}
                            </span>

                            <div className="flex items-center gap-2 shrink-0">
                              <span className="text-[10px] text-muted-foreground font-semibold whitespace-nowrap">
                                {formatNotificationTime(notification.created_at)}
                              </span>
                              <ChevronDown className={cn(
                                "h-4 w-4  transition-all duration-300",
                                expandedId === notification.id ? "rotate-180 text-primary" : "group-hover:text-muted-foreground/60"
                              )} />
                            </div>
                          </div>
                        </div>

                        {/* Expandable Content Section */}
                        <div className={cn(
                          "grid transition-all duration-300 ease-in-out",
                          expandedId === notification.id ? "grid-rows-[1fr] opacity-100  border-t border-border/5" : "grid-rows-[0fr] opacity-0"
                        )}>
                          <div className="overflow-hidden ml-12 pr-1">
                            <p className="text-xs text-muted-foreground/90 leading-relaxed mb-1 font-medium">
                              {notification.desc}
                            </p>

                            <div className="flex items-center justify-between pt-1 border-t border-border/40">
                              <span className={cn(
                                "text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-sm bg-muted/40",
                                getNotificationTypeColor(notification.group)
                              )}>
                                {notification.group}
                              </span>

                              {!notification.is_read && (
                                <Button
                                  size="sm"
                                  variant="secondary"
                                  disabled={readingId === notification.id}
                                  className="h-7 px-3 text-[10px] font-bold bg-primary/10 text-primary hover:bg-primary/20 rounded-md shadow-sm transition-all active:scale-95"
                                  onClick={(e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    handleNotificationClick(notification.id);
                                  }}
                                >
                                  {readingId === notification.id ? (
                                    <Loader2 className="h-3 w-3 animate-spin mr-1.5" />
                                  ) : (
                                    <CheckCircle2 className="h-3.5 w-3.5 mr-1.5" />
                                  )}
                                  Mark read
                                </Button>
                              )}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="flex flex-col items-center justify-center py-28 px-4 text-center group">
                  <div className="p-4 rounded-full bg-accent/5 group-hover:bg-accent/10 transition-colors mb-4">
                    <Bell className="h-12 w-12 text-muted-foreground/30 stroke-[1.25]" />
                  </div>
                  <p className="text-sm font-bold text-muted-foreground/60 uppercase tracking-widest">Nothing New</p>
                  <p className="text-[10px] text-muted-foreground/40 mt-1">Check back later for updates</p>
                </div>
              )}
            </div>
          </ScrollArea>

          {/* Footer Section */}
          <div className="border-t border-border/60 p-2 bg-muted/10">
            <DropdownMenuItem asChild className="p-0 focus:bg-transparent">
              <Link
                href="/notifications"
                className="flex w-full items-center justify-center h-10 text-[11px] font-bold text-primary hover:bg-primary/10 rounded-md transition-all uppercase tracking-widest cursor-pointer"
              >
                View all notifications
              </Link>
            </DropdownMenuItem>
          </div>
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
