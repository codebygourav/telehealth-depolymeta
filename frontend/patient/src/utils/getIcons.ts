// src/utils/getIcons.ts
import {
    CheckCircle,
    XCircle,
    Clock,
    CreditCard,
    Smartphone,
    Banknote,
    Calendar,
    FileText,
    Pill,
    MessageSquare,
    Bell
} from "lucide-react";

// Transaction Status Icons
export const getTransactionStatusIcon = (status: string) => {
    switch (status?.toLowerCase()) {
        case "paid":
        case "success":
        case "completed":
            return {
                icon: CheckCircle,
                color: "text-green-500",
                bg: "bg-green-50 dark:bg-green-950/30"
            };
        case "pending":
        case "processing":
            return {
                icon: Clock,
                color: "text-yellow-500",
                bg: "bg-yellow-50 dark:bg-yellow-950/30"
            };
        case "cancelled":
        case "canceled":
            return {
                icon: XCircle,
                color: "text-red-500",
                bg: "bg-red-50 dark:bg-red-950/30"
            };
        case "failed":
        case "error":
            return {
                icon: XCircle,
                color: "text-red-500",
                bg: "bg-red-50 dark:bg-red-950/30"
            };
        default:
            return {
                icon: Clock,
                color: "text-gray-500",
                bg: "bg-gray-50 dark:bg-gray-950/30"
            };
    }
};

// Payment Method Icons
export const getPaymentMethodIcon = (method: string) => {
    switch (method?.toLowerCase()) {
        case "card":
            return {
                icon: CreditCard,
                color: "text-blue-500",
                bg: "bg-blue-50 dark:bg-blue-950/30"
            };
        case "upi":
            return {
                icon: Smartphone,
                color: "text-purple-500",
                bg: "bg-purple-50 dark:bg-purple-950/30"
            };
        default:
            return {
                icon: Banknote,
                color: "text-gray-500",
                bg: "bg-gray-50 dark:bg-gray-950/30"
            };
    }
};

// Notification Icons
export const getNotificationIcon = (group: string) => {
    switch (group?.toLowerCase()) {
        case "appointment":
            return {
                icon: Calendar,
                color: "text-blue-500",
                bg: "bg-blue-50 dark:bg-blue-950/30",
                label: "Appointment"
            };
        case "lab":
        case "lab_result":
            return {
                icon: FileText,
                color: "text-purple-500",
                bg: "bg-purple-50 dark:bg-purple-950/30",
                label: "Lab Result"
            };
        case "prescription":
            return {
                icon: Pill,
                color: "text-green-500",
                bg: "bg-green-50 dark:bg-green-950/30",
                label: "Prescription"
            };
        case "message":
            return {
                icon: MessageSquare,
                color: "text-amber-500",
                bg: "bg-amber-50 dark:bg-amber-950/30",
                label: "Message"
            };
        case "reminder":
            return {
                icon: Bell,
                color: "text-orange-500",
                bg: "bg-orange-50 dark:bg-orange-950/30",
                label: "Reminder"
            };
        default:
            return {
                icon: Bell,
                color: "text-gray-500",
                bg: "bg-gray-50 dark:bg-gray-950/30",
                label: "Notification"
            };
    }
};

// Size classes helper
export const getIconSizeClass = (size: "sm" | "md" | "lg" = "md") => {
    const sizes = {
        sm: "w-3 h-3 sm:w-4 sm:h-4",
        md: "w-4 h-4 sm:w-5 sm:h-5",
        lg: "w-5 h-5 sm:w-6 sm:h-6"
    };
    return sizes[size];
};