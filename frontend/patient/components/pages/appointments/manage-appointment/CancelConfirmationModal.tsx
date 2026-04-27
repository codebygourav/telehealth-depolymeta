"use client"
import { motion } from 'motion/react';
import { X } from 'lucide-react';

interface CancelConfirmationModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    isPending?: boolean;
}

export default function CancelConfirmationModal({
    isOpen,
    onClose,
    onConfirm,
    isPending = false
}: CancelConfirmationModalProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                onClick={onClose}
                className="absolute inset-0 bg-primary/40 backdrop-blur-sm"
            />
            <motion.div
                initial={{ opacity: 0, scale: 0.9, y: 20 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.9, y: 20 }}
                className="relative w-full max-w-md bg-white rounded-[40px] shadow-2xl overflow-hidden"
            >
                <div className="p-10 text-center">
                    <div className="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <X className="w-10 h-10 text-red-500" />
                    </div>

                    <h3 className="text-2xl font-bold font-headline text-primary mb-4 italic">Confirm Cancel</h3>
                    <p className="text-on-surface-variant text-sm font-medium leading-relaxed mb-10 italic">
                        Cancel an existing appointment and free up the scheduled time slot.
                    </p>

                    <div className="grid grid-cols-2 gap-4">
                        <button
                            onClick={onClose}
                            disabled={isPending}
                            className="py-4 bg-white text-primary border border-outline-variant/20 rounded-full font-bold text-sm italic hover:bg-red-50 hover:text-red-700 hover:border-red-200 transition-all disabled:opacity-50"
                        >
                            No
                        </button>
                        <button
                            onClick={onConfirm}
                            disabled={isPending}
                            className="py-4 bg-[#0A2E1F] text-white rounded-2xl font-bold text-sm italic shadow-lg shadow-primary/10 hover:bg-emerald-950 transition-all disabled:opacity-50 flex items-center justify-center"
                        >
                            {isPending ? (
                                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                            ) : (
                                "Yes"
                            )}
                        </button>
                    </div>
                </div>
            </motion.div>
        </div>
    );
}
