"use client";

import { ReactNode } from "react";
import { X } from "lucide-react";
import { Button } from "@base-ui/react/button";

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
            <div className="relative w-full max-w-md p-6 text-center bg-white shadow-xl rounded-2xl">

                {/* Close Button */}
                <Button
                    onClick={onClose}
                    className="absolute text-gray-400 right-4 top-4 hover:text-gray-700"
                >
                    <X className="w-5 h-5 cursor-pointer" />
                </Button>

                {/* Icon */}
                <div
                    className={`mx-auto mb-4 flex h-14 w-14 items-center justify-center global-radius-10  ${type === "danger" ? "bg-red-100" : "bg-green-100"
                        }`}
                >
                    {icon}
                </div>

                {/* Title */}
                <h2 className="text-lg font-semibold">{title}</h2>

                {/* Description */}
                {description && (
                    <p className="mt-1 text-sm text-gray-500">{description}</p>
                )}

                {/* Buttons */}
                <div className="flex gap-4 mt-6">
                    {type === "danger" && (
                        <Button
                            onClick={onClose}
                            className="flex-1 text-xs font-medium btn-primary-cta"
                        >
                            {cancelText}
                        </Button>
                    )}
                    <Button
                        onClick={onConfirm}
                        disabled={loading}
                        className={`flex-1 rounded-lg py-2 text-xs font-medium  ${type === "danger"
                            ? "btn-primary-cta-outline"
                            : "btn-primary-cta"
                            }`}
                    >
                        {loading ? "Processing..." : confirmText}
                    </Button>
                </div>
            </div>
        </div>
    );
}