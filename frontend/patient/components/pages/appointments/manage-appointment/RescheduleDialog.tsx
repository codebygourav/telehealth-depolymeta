"use client"
import { useState } from 'react';
import { X, Calendar, Clock, ChevronLeft, Loader2 } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import { useDoctorAvailableSlots } from '@/queries/useDoctorAvailableSlots';
import { SlotItem, SlotGroup } from '@/types/slots';
import { toast } from 'sonner';
import SuccessDialog from './SuccessDialog';
import ErrorDialog from './ErrorDialog';

interface RescheduleDialogProps {
    isOpen: boolean;
    onClose: () => void;
    doctorId: string;
    appointmentId?: string;
    isLoading?: boolean;
    onConfirmReschedule?: (slot: SlotItem, callbacks: { onSuccess: (message: string) => void, onError: (message: string) => void }) => void;

    currentSlotId?: string;
    currentSlotDate?: string;
    isAlreadyRescheduled?: boolean;
}

export default function RescheduleDialog({
    isOpen,
    onClose,
    doctorId,
    appointmentId,
    isLoading,
    onConfirmReschedule,
     currentSlotId,
    currentSlotDate,
    isAlreadyRescheduled
}: RescheduleDialogProps) {
    const [selectedSlot, setSelectedSlot] = useState<SlotItem | null>(null);
    const [showSuccessDialog, setShowSuccessDialog] = useState(false);
    const [showErrorDialog, setShowErrorDialog] = useState(false);
    const [dialogMessage, setDialogMessage] = useState('');
    const [dialogTitle, setDialogTitle] = useState('');

    // Generate unique key for slot (handles recurring slots with same IDs)
    const getSlotKey = (slot: SlotItem) => `${slot.id}-${slot.date}`;

    // Fetch available slots when dialog is open
    const { data: slotsData, isLoading: isLoadingSlots, error: slotsError } = useDoctorAvailableSlots(
        doctorId,
        isOpen
    );

    

    const slotGroups: SlotGroup[] = slotsData?.data || [];

    const handleSlotSelect = (slot: SlotItem) => {
        setSelectedSlot(slot);
    };

    const handleConfirm = () => {
        if (isAlreadyRescheduled) {
            setDialogTitle('Error');
            setDialogMessage('You already rescheduled this appointment');
            setShowErrorDialog(true);
            return;
        }


        if (!selectedSlot) {
            setDialogTitle('Error');
            setDialogMessage('Please select a time slot');
            setShowErrorDialog(true);
            return;
        }
        if (onConfirmReschedule) {
            onConfirmReschedule(selectedSlot, {
                onSuccess: (message: string) => {
                    setDialogTitle('Success');
                    setDialogMessage(message || 'Appointment rescheduled successfully');
                    setShowSuccessDialog(true);
                    setSelectedSlot(null);
                },
                onError: (message: string) => {
                    setDialogTitle('Error');
                    setDialogMessage(message || 'Failed to reschedule appointment');
                    setShowErrorDialog(true);
                }
            });
        }
    };

    const handleSuccessClose = () => {
        setShowSuccessDialog(false);
        onClose();
    };

    const handleErrorClose = () => {
        setShowErrorDialog(false);
    };

    const formatDate = (dateStr: string) => {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        });
    };

    return (
        <>
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
                            className="relative w-full max-w-2xl max-h-[85vh] bg-white rounded-[40px] shadow-2xl overflow-hidden flex flex-col"
                        >
                            {/* Header */}
                            <div className="flex items-center gap-4 p-6 border-b border-outline-variant/10">
                                <button
                                    onClick={onClose}
                                    className="p-2 hover:bg-surface-container rounded-full transition-colors"
                                >
                                    <ChevronLeft className="w-5 h-5 text-primary" />
                                </button>
                                <h3 className="text-xl font-bold font-headline text-primary">Reschedule Appointment</h3>
                            </div>

                            {/* Content */}
                            <div className="flex-1 overflow-y-auto p-6">

                                {isAlreadyRescheduled && (
                                    <div className="text-red-500 text-sm text-center mb-4 font-semibold">
                                        You already rescheduled this appointment
                                    </div>
                                )}

                                {isLoadingSlots ? (
                                    <div className="flex flex-col items-center justify-center py-12">
                                        <Loader2 className="w-8 h-8 text-emerald-600 animate-spin mb-4" />
                                        <p className="text-sm text-on-surface-variant">Loading available slots...</p>
                                    </div>
                                ) : slotsError ? (
                                    <div className="text-center py-12">
                                        <div className="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <X className="w-8 h-8 text-red-500" />
                                        </div>
                                        <p className="text-sm text-on-surface-variant">Failed to load slots</p>
                                        <button
                                            onClick={onClose}
                                            className="mt-4 text-emerald-600 font-bold text-sm hover:underline"
                                        >
                                            Close
                                        </button>
                                    </div>
                                ) : slotGroups.length === 0 ? (
                                    <div className="text-center py-12">
                                        <div className="w-16 h-16 bg-surface-container-low rounded-full flex items-center justify-center mx-auto mb-4 text-on-surface-variant/30">
                                            <Calendar className="w-8 h-8" />
                                        </div>
                                        <p className="text-sm text-on-surface-variant">No available slots found</p>
                                    </div>
                                ) : (
                                    <div className="space-y-6">
                                        {slotGroups.map((group) => (
                                            <div key={group.date} className="space-y-3">
                                                <h4 className="text-sm font-bold text-primary flex items-center gap-2">
                                                    <Calendar className="w-4 h-4 text-emerald-600" />
                                                    {formatDate(group.date)}
                                                    <span className="text-xs font-normal text-on-surface-variant/60">
                                                        ({group.slots.length} slots)
                                                    </span>
                                                </h4>
                                                <div className="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                                    {group.slots.map((slot) => {
                                                        const isCurrentSlot =
                                                            currentSlotId === slot.id && currentSlotDate === slot.date;

                                                        return (
                                                            <button
                                                                key={getSlotKey(slot)}
                                                                onClick={() => handleSlotSelect(slot)}
                                                                disabled={!slot.available || isCurrentSlot}
                                                                className={`p-3 rounded-xl text-xs font-bold transition-all ${selectedSlot && getSlotKey(selectedSlot) === getSlotKey(slot)
                                                                        ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20'
                                                                        : slot.available && !isCurrentSlot
                                                                            ? 'bg-surface-container-low text-primary hover:bg-emerald-50 border border-outline-variant/10'
                                                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                                    }`}
                                                            >
                                                                <div className="flex items-center justify-center gap-1 mb-1">
                                                                    {slot.start_time}
                                                                </div>

                                                                {isCurrentSlot && (
                                                                    <div className="text-[10px] text-red-500">
                                                                        Already booked
                                                                    </div>
                                                                )}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Footer */}
                            <div className="border-t border-outline-variant/10 p-6">
                                <div className="flex items-center justify-between">
                                    <div className="text-sm">
                                        {selectedSlot ? (
                                            <div className="text-primary">
                                                <span className="font-bold">Selected:</span>{' '}
                                                {formatDate(selectedSlot.date)} at {selectedSlot.start_time}
                                            </div>
                                        ) : (
                                            <span className="text-on-surface-variant">Select a time slot</span>
                                        )}
                                    </div>
                                    <div className="flex gap-3">
                                        <button
                                            onClick={onClose}
                                            className="px-6 py-3 bg-white text-primary border border-outline-variant/20 rounded-2xl font-bold text-sm hover:bg-emerald-50 transition-all"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            onClick={handleConfirm}
                                            disabled={!selectedSlot || isLoadingSlots || isLoading || isAlreadyRescheduled}
                                            className="px-6 py-3 bg-[#0A2E1F] text-white rounded-2xl font-bold text-sm hover:opacity-90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                        >
                                            {isLoading && <Loader2 className="w-4 h-4 animate-spin" />}
                                            Confirm
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </motion.div>
                    </div>
                )}
            </AnimatePresence>

            <SuccessDialog
                isOpen={showSuccessDialog}
                onClose={handleSuccessClose}
                title={dialogTitle}
                message={dialogMessage}
            />

            <ErrorDialog
                isOpen={showErrorDialog}
                onClose={handleErrorClose}
                title={dialogTitle}
                message={dialogMessage}
            />
        </>
    );
}
