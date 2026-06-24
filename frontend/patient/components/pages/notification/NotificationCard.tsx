"use client";

import { Card, CardContent } from "@/components/ui/card";
import {
  Bell,
  FileText,
  Pill,
  Calendar,
  CreditCard,
  MessageSquare,
  Clock,
  CheckCheck,
  Shield,
} from "lucide-react";

const getIcon = (group: string) => {
  switch (group?.toLowerCase()) {
    case "appointment":
      return {
        icon: Calendar,
        color: "text-blue-500",
        bg: "bg-blue-50 dark:bg-blue-950/30",
      };
    case "lab":
    case "lab_result":
      return {
        icon: FileText,
        color: "text-purple-500",
        bg: "bg-purple-50 dark:bg-purple-950/30",
      };
    case "prescription":
      return {
        icon: Pill,
        color: "text-green-500",
        bg: "bg-green-50 dark:bg-green-950/30",
      };
    case "message":
      return {
        icon: MessageSquare,
        color: "text-amber-500",
        bg: "bg-amber-50 dark:bg-amber-950/30",
      };
    case "reminder":
      return {
        icon: Bell,
        color: "text-orange-500",
        bg: "bg-orange-50 dark:bg-orange-950/30",
      };
    case "payment":
      return {
        icon: CreditCard,
        color: "text-primary",
        bg: "bg-emerald-50 dark:bg-emerald-950/30",
      };
    case "system":
      return {
        icon: Bell,
        color: "text-red-500",
        bg: "bg-red-50 dark:bg-red-950/30",
      };
    case "vaccination":
      return {
        icon: Shield,
        color: "text-rose-500",
        bg: "bg-rose-50 dark:bg-rose-950/30",
      };
    default:
      return {
        icon: Bell,
        color: "text-gray-500",
        bg: "bg-gray-50 dark:bg-gray-950/30",
      };
  }
};

interface NotificationCardProps {
  notification: {
    id: string | number;
    title: string;
    desc: string;
    group: string;
    is_read: boolean;
    created_at: string;
  };
  onMarkAsRead: (id: string | number) => void;
}

export function NotificationCard({
  notification,
  onMarkAsRead,
}: NotificationCardProps) {
  const { icon: Icon, color, bg } = getIcon(notification.group);

  return (
    <Card
      className={`rounded-lg p-5 border border-[#E7E8EB] shadow-[0px_2px_4px_0px_#0000001A]${!notification.is_read ? "bg-muted/5" : ""}`}
      onClick={() => console.log("View notification:", notification.id)}
    >
      <CardContent className="relative pl-0">
        <div className="relative flex items-start gap-4">
          <div className={`p-2.5 rounded-xl ${bg} shrink-0`}>
            <Icon className={`h-6 w-6 ${color}`} />
          </div>

          {!notification.is_read && (
            <div className="absolute -top-2 -right-5 h-3 w-3 bg-green-500 rounded-full ring-2 ring-white dark:ring-gray-950 animate-pulse" />
          )}

          <div className="flex-1 min-w-0">
            <div className="flex items-start justify-between gap-2 flex-wrap mb-1">
              <h3
                className={`text-[#1F1E1E] text-lg font-semibold ${!notification.is_read ? "text-foreground" : "text-muted-foreground"}`}
              >
                {notification.title}
              </h3>
              <div className="flex items-center gap-1 text-xs text-muted-foreground">
                <Clock className="h-3 w-3" />
                {notification.created_at}
              </div>
            </div>
            <p className="text-base text-[#4D4D4D]">{notification.desc}</p>
          </div>
        </div>

        {!notification.is_read && (
          <CheckCheck
            onClick={async (e) => {
              e.stopPropagation();
              onMarkAsRead(notification.id);
            }}
            className="h-5 w-5 absolute right-3 md:bottom-2 cursor-pointer text-muted-foreground hover:text-green-500 transition"
          />
        )}
      </CardContent>
    </Card>
  );
}
