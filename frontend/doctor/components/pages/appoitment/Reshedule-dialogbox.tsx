"use client";

import { getDoctorSlots } from "@/api/resheodule";
import CustomDialog from "@/components/custom/Dialogboxs";
import { Button } from "@/components/ui";
import { Dialog, DialogContent, DialogTitle } from "@/components/ui/dialog";
import { useAuth } from "@/context/userContext";
import { rescheduleAppointment } from "@/mutations/reschedule";
import { useEffect, useState } from "react";
import { X } from "lucide-react";

interface RescheduleAppointmentDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    appointmentId: string;
    setCustomDialogOpen: (val: boolean) => void;
    setDialogData: (data: any) => void;
}

export function RescheduleAppointmentDialog({
    open,
    onOpenChange,
    appointmentId,
    setCustomDialogOpen,
    setDialogData,
}: RescheduleAppointmentDialogProps) {
    const [selectedDate, setSelectedDate] = useState("");
    const [selectedSlot, setSelectedSlot] = useState<any>(null);
    const [slots, setSlots] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    const { user } = useAuth();

    useEffect(() => {
        if (open && user?.doctor_id) {
            fetchSlots();
        }
    }, [open, user]);

    const fetchSlots = async () => {
        try {
            const doctorId = user?.doctor_id;
            if (!doctorId) return;

            const res = await getDoctorSlots(doctorId);

            const formattedSlots = res?.data?.flatMap((dayItem: any) =>
                dayItem.slots.map((slot: any) => {
                    const dateObj = new Date(slot.date);
                    const day = dateObj.getDate();
                    const day_name = slot.day_of_week
                        ? slot.day_of_week.charAt(0).toUpperCase() + slot.day_of_week.slice(1)
                        : "";

                    return { ...slot, day, day_name };
                })
            );

            setSlots(formattedSlots || []);

            if (formattedSlots.length > 0) {
                setSelectedDate(formattedSlots[0].date);
            }

            setSelectedSlot(null);
        } catch (err) {
            console.log("Slot fetch error", err);
        }
    };

    const uniqueDates = Array.from(new Set(slots.map((slot) => slot.date)));

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-[95vw] sm:max-w-[90vw] md:max-w-3xl! rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-6 max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 mb-3 sm:mb-4">
                    <div className="text-base sm:text-lg md:text-xl font-semibold">
                        <DialogTitle className="text-base sm:text-lg md:text-xl">
                            Select Schedules
                        </DialogTitle>
                    </div>
                    <span className="text-xs sm:text-sm text-muted-foreground">
                        {selectedDate
                            ? (() => {
                                const slotForDate = slots.find((s) => s.date === selectedDate);
                                if (!slotForDate) return "Select a date";
                                return `${slotForDate.day_name}, ${slotForDate.day} ${new Date(
                                    slotForDate.date
                                ).toLocaleString("default", { month: "long" })} ${new Date(
                                    slotForDate.date
                                ).getFullYear()}`;
                            })()
                            : "Select a date"}
                    </span>
                </div>

                {/* Dates Horizontal Scroll */}
                {uniqueDates.length > 0 && (
                    <div className="flex gap-2 sm:gap-3 mt-2 overflow-x-auto overflow-y-hidden scrollbar-hide pb-2">
                        {uniqueDates.map((date) => {
                            const slotForDate = slots.find((s) => s.date === date);
                            return (
                                <button
                                    key={date}
                                    onClick={() => {
                                        setSelectedDate(date);
                                        setSelectedSlot(null);
                                    }}
                                    className={`flex flex-col items-center justify-center p-2 sm:p-3 rounded-lg text-center transition-all duration-200 min-w-[60px] sm:min-w-[70px] ${selectedDate === date
                                        ? "bg-primary text-white shadow-md scale-105"
                                        : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                                        }`}
                                >
                                    <span className="text-sm sm:text-base font-semibold">
                                        {slotForDate?.day}
                                    </span>
                                    <span className="text-[10px] sm:text-xs">
                                        {slotForDate?.day_name?.slice(0, 3)}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                )}

                {/* Time Slots Grid */}
                <div className="mt-4 sm:mt-6">
                    <h3 className="text-xs sm:text-sm font-medium text-muted-foreground mb-2 sm:mb-3">
                        Available Time Slots
                    </h3>
                    <div className="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 gap-2 sm:gap-3">
                        {slots
                            .filter((s) => s.date === selectedDate)
                            .map((slot) => (
                                <div
                                    key={slot.id}
                                    onClick={() => setSelectedSlot(slot)}
                                    className={`py-2 sm:py-3 px-1 sm:px-2 flex flex-col items-center justify-center text-center cursor-pointer rounded-lg transition-all duration-200 ${selectedSlot?.id === slot.id
                                        ? "bg-primary text-white shadow-md scale-105"
                                        : "bg-gray-100 hover:bg-green-100 hover:scale-105"
                                        }`}
                                >
                                    <p className="font-medium text-[10px] xs:text-xs sm:text-sm">
                                        {slot.start_time} - {slot.end_time}
                                    </p>
                                    <p className="text-[8px] xs:text-[9px] sm:text-xs mt-0.5 opacity-90">
                                        {slot.consultation_type_label}
                                        {slot.consultation_type === "in-person" && slot.opd_type
                                            ? ` (${slot.opd_type})`
                                            : ""}
                                    </p>
                                </div>
                            ))}
                    </div>
                    {slots.filter((s) => s.date === selectedDate).length === 0 && (
                        <div className="text-center py-6 sm:py-8">
                            <p className="text-xs sm:text-sm text-muted-foreground">
                                No available slots for this date
                            </p>
                        </div>
                    )}
                </div>

                {/* Reschedule Button */}
                <button
                    disabled={!selectedSlot || loading}
                    onClick={async () => {
                        if (!selectedSlot) return;

                        const payload = {
                            appointment_id: appointmentId,
                            availability_id: selectedSlot.id,
                            appointment_date: selectedSlot.date,
                            appointment_time: selectedSlot.booking_start_time,
                        };

                        console.log("Reschedule payload:", payload);

                        try {
                            setLoading(true);
                            const res = await rescheduleAppointment(payload);

                            console.log("✅ FULL API RESPONSE:", res);
                            console.log("✅ SUCCESS:", res.success);
                            console.log("✅ MESSAGE:", res.message);
                            console.log("✅ DATA:", res.data);

                            if (res.success) {
                                setDialogData({
                                    title: "Appointment Rescheduled",
                                    description: res.message,
                                    type: "success",
                                });

                                onOpenChange(false);
                                setCustomDialogOpen(true);
                            } else {
                                setDialogData({
                                    title: "Error",
                                    description: res.message || "Something went wrong.",
                                    type: "danger",
                                });
                                onOpenChange(false);
                                setCustomDialogOpen(true);
                            }
                        } catch (err: any) {
                            console.error("Error rescheduling:", err);

                            setDialogData({
                                title: "Validation Error",
                                description:
                                    err.response?.data?.errors?.message ||
                                    err.response?.data?.message ||
                                    "Something went wrong",
                                type: "danger",
                            });

                            onOpenChange(false);
                            setCustomDialogOpen(true);
                        } finally {
                            setLoading(false);
                        }
                    }}
                    className={`w-full mt-4 sm:mt-6 py-2.5 sm:py-3 rounded-lg font-semibold transition-all duration-200 text-sm sm:text-base ${selectedSlot && !loading
                        ? "bg-primary text-white hover:bg-primary/90 active:scale-98"
                        : "bg-gray-300 text-gray-500 cursor-not-allowed"
                        }`}
                >
                    {loading ? (
                        <div className="flex items-center justify-center gap-2">
                            <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                            Rescheduling...
                        </div>
                    ) : (
                        "Reschedule"
                    )}
                </button>
            </DialogContent>
        </Dialog>
    );
}