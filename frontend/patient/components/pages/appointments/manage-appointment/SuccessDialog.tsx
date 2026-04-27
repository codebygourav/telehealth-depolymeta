"use client"
import { Check } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

interface SuccessDialogProps {
    isOpen: boolean;
    onClose: () => void;
    message: string;
    title?: string;
}

export default function SuccessDialog({
    isOpen,
    onClose,
    message,
    title = "Success"
}: SuccessDialogProps) {
    return (
        <AnimatePresence>
            {isOpen && (
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
                            <div className="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <Check className="w-10 h-10 text-emerald-600" />
                            </div>

                            <h3 className="text-2xl font-bold font-headline text-primary mb-4">{title}</h3>
                            <p className="text-on-surface-variant text-sm font-medium leading-relaxed mb-10">
                                {message}
                            </p>

                            <button
                                onClick={onClose}
                                className="w-full py-4 bg-[#0A2E1F] text-white rounded-2xl font-bold text-lg shadow-lg shadow-primary/10 hover:opacity-90 transition-all"
                            >
                                OK
                            </button>
                        </div>
                    </motion.div>
                </div>
            )}
        </AnimatePresence>
    );
}
