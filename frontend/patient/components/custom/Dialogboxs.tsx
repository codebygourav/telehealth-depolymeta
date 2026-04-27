"use client";

import { ReactNode } from "react";
import { X } from "lucide-react";

interface CustomDialogProps {
    open: boolean;
    onClose: () => void;
    icon?: ReactNode;
    title: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    onConfirm?: () => void;
    loading?: boolean;
    type?: "danger" | "success";
}

export default function CustomDialog({
    open,
    onClose,
    icon,
    title,
    description,
    confirmText = "Confirm",
    cancelText = "Cancel",
    onConfirm,
    loading,
    type = "danger",
}: CustomDialogProps) {
    if (!open) return null;


    

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">

            {/* Box */}
            <div className="relative w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-xl">

                {/* Close Button */}
                <button
                    onClick={onClose}
                    className="absolute right-4 top-4 text-gray-400 hover:text-gray-700"
                >
                    <X className="h-5 w-5" />
                </button>

                {/* Icon */}
                <div
                    className={`mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full ${type === "danger" ? "bg-red-100" : "bg-green-100"
                        }`}
                >
                    {icon}
                </div>

                {/* Title */}
                <h2 className="text-lg font-semibold">{title}</h2>

                {/* Description */}
                {description && (
                    <p className="text-sm text-gray-500 mt-1">{description}</p>
                )}

                {/* Buttons */}
                <div className="flex gap-4 mt-6">
                    {type === "danger" && (
                        <button
                            onClick={onClose}
                            className="flex-1 rounded-lg bg-gray-100 text-xs  font-medium hover:bg-gray-200"
                        >
                            {cancelText}
                        </button>
                    )}

                    <button
                        onClick={onConfirm}
                        disabled={loading}
                        className={`flex-1 rounded-lg py-2 text-xs font-medium text-white ${type === "danger"
                            ? "bg-primary hover:bg-green-800"
                            : "bg-primary hover:bg-green-800"
                            }`}
                    >
                        {loading ? "Processing..." : confirmText}
                    </button>
                </div>
            </div>
        </div>
    );
}