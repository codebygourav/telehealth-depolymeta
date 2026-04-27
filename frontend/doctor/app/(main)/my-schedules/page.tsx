"use client"

import { useMyAppointments } from "@/queries/useAppointments";
import { useMySchedules } from "@/queries/getMySchedules";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Calendar } from "@/components/ui/calendar"
import { useState, useMemo, useEffect } from "react";
import { Badge } from "@/components/ui/badge";
import { format } from "date-fns";
import { useRouter } from "next/navigation";
import { Appointment } from "@/types/appointment";
import { ScheduleDay, OPDSlot } from "@/types/schedule";
import DoctorOpdSchedule from "@/components/pages/my-schedules/DoctorOpdSchedule";
import BookAppointments from "@/components/pages/my-schedules/BookAppointments";
import { Stethoscope } from "lucide-react";

export const filterAppointmentsByDate = (
    appointments: any[],
    selectedDate?: Date
) => {
    if (!selectedDate) return [];

    const formattedDate = selectedDate.toLocaleDateString("en-CA");
    // ✅ gives YYYY-MM-DD in LOCAL timezone

    return appointments.filter(
        (apt) => apt.appointment_date === formattedDate
    );
};

const MySchedulesPage = () => {

    const { data, isLoading, error } = useMyAppointments("all");
    const { data: scheduleData, isLoading: scheduleLoading, error: scheduleError } = useMySchedules();

    const [selectedDate, setSelectedDate] = useState<Date | undefined>(new Date());
    const [currentDate, setCurrentDate] = useState<Date>(new Date());
    const [selectedSlot, setSelectedSlot] = useState<OPDSlot | undefined>(undefined);
    const router = useRouter();

    const filteredAppointments = useMemo(() => {
        const dateFiltered = filterAppointmentsByDate(
            data?.data || [],
            selectedDate
        );

        if (selectedSlot) {
            // Priority 1: Use appointments inside the slot if available
            if (selectedSlot.appointments && selectedSlot.appointments.length > 0) {
                return selectedSlot.appointments;
            }

            // Priority 2: Filter the date-filtered appointments by matching time or slot link
            // Most appointments will match the slot's start_time or fall within time_range
            return dateFiltered.filter((appt: any) => {
                const apptTime = appt.appointment_time; // format like "18:00:00" or "06:00 PM"
                const slotStart = selectedSlot.start_time; // format like "18:00:00"

                // Try direct match first (often both are in HH:mm:ss format from API)
                if (apptTime === slotStart) return true;

                // Fallback: compare formatted times if they exist
                const apptFormatted = appt.appointment_time_formatted; // e.g. "06:00 PM"
                const slotTimeRange = selectedSlot.time_range; // e.g. "6:00 PM - 7:30 PM"
                if (apptFormatted && slotTimeRange && slotTimeRange.startsWith(apptFormatted.replace(/^0/, ''))) {
                    return true;
                }

                // General check: see if appt time is mentioned in slot time range string
                if (apptFormatted && slotTimeRange && slotTimeRange.includes(apptFormatted)) {
                    return true;
                }

                return false;
            });
        }

        return dateFiltered;
    }, [data, selectedDate, selectedSlot]);

    const getOPDSlotsForDate = (date: Date | undefined): OPDSlot[] => {
        if (!date) return [];
        const day = date.getDate();
        const month = date.getMonth();
        const year = date.getFullYear();

        const scheduleDay = scheduleData?.data?.days?.find((s: ScheduleDay) => {
            const sDate = new Date(s.date);
            return sDate.getDate() === day && sDate.getMonth() === month && sDate.getFullYear() === year;
        });

        return scheduleDay?.slots || [];
    };

    const onSlotClick = (slot: OPDSlot) => {
        setSelectedSlot(slot);
    };

    const onViewAllSlots = (slots: OPDSlot[]) => {
        console.log("Viewing all slots:", slots);
        // Implement view all logic if needed
    };

    const onDateClick = (date: Date | undefined) => {
        setSelectedDate(date);
        const slots = getOPDSlotsForDate(date);
        setSelectedSlot(slots.length > 0 ? slots[0] : undefined);
    };

    // Auto-select first slot when schedule data is loaded or date changes
    useEffect(() => {
        if (selectedDate && scheduleData?.data) {
            const slots = getOPDSlotsForDate(selectedDate);
            if (slots.length > 0 && !selectedSlot) {
                setSelectedSlot(slots[0]);
            }
        }
    }, [selectedDate, scheduleData, selectedSlot]);

    const onMonthChange = (month: Date) => {
        setCurrentDate(month);
    };

    const getOPDCount = (date: Date) => {
        const slots = getOPDSlotsForDate(date);
        return slots.length;
    };

    const hasAppointments = (date: Date) => {
        const day = date.getDate();
        const month = date.getMonth();
        const year = date.getFullYear();
        return data?.data?.some((appt: Appointment) => {
            const apptDate = new Date(appt.appointment_date);
            return apptDate.getDate() === day && apptDate.getMonth() === month && apptDate.getFullYear() === year;
        }) || false;
    };

    const isToday = (date: Date) => {
        const today = new Date();
        return date.getDate() === today.getDate() &&
            date.getMonth() === today.getMonth() &&
            date.getFullYear() === today.getFullYear();
    };

    return (
        <div>

            <div className="space-y-1 sm:space-y-2">
                <h1 className="text-xl sm:text-2xl md:text-3xl font-bold tracking-tight text-primary">
                    Doctor's Schedule
                </h1>
                <p className="text-xs sm:text-sm text-muted-foreground">
                    Manage your OPD appointments and availability
                </p>
            </div>

            <Card className="border-border mt-5">

                <CardHeader>
                    <CardTitle>
                        {format(currentDate, "MMMM yyyy")}
                    </CardTitle>
                    <CardDescription>
                        <span className="font-semibold text-primary">OPD sessions</span> this month
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-3 mt-2">

                        {/* Left Column - Calendar */}
                        <div className="lg:col-span-1">
                            <Calendar
                                mode="single"
                                selected={selectedDate}
                                onSelect={onDateClick}
                                month={currentDate}
                                onMonthChange={onMonthChange}
                                className="rounded-lg border gap-1 w-full"
                                components={{
                                    DayButton: ({ day, ...props }) => {
                                        const date = day.date;
                                        const count = getOPDCount(date);
                                        const isSelected = selectedDate?.toDateString() === date.toDateString();
                                        const isTodayDate = isToday(date);
                                        const hasAppt = hasAppointments(date);
                                        return (
                                            <button
                                                {...props}
                                                className={`
                                                    relative flex items-center justify-center
                                                    aspect-square w-full
                                                    text-sm font-normal rounded-md transition-all duration-200
                                                    h-auto mx-auto z-10
                                                    ${isSelected
                                                        ? 'bg-primary text-primary-foreground shadow-sm scale-105'
                                                        : ''
                                                    }
                                                    ${isTodayDate && !isSelected
                                                        ? 'bg-primary/5 text-primary hover:bg-primary/10 font-bold border border-primary'
                                                        : ''
                                                    }
                                                    ${hasAppt && !isSelected && !isTodayDate
                                                        ? 'bg-primary/5 text-primary hover:bg-primary/10 cursor-pointer'
                                                        : ''
                                                    }
                                                    ${!hasAppt && !isTodayDate && !isSelected
                                                        ? 'text-muted-foreground'
                                                        : ''
                                                    }
                                                `}
                                            >
                                                <div className="flex flex-col items-center justify-center">
                                                    <span>{date.getDate()}</span>
                                                    {count > 0 && (
                                                        <span className="absolute md:bottom-1 -bottom-1 left-1/2 transform -translate-x-1/2">
                                                            <Badge
                                                                variant="outline"
                                                                className={`h-4 md:px-1 px-0.5 md:text-[8px] text-[7px] bg-primary/10 ${isSelected ? 'text-white' : ''}`}
                                                            >
                                                                {count} {count === 1 ? 'OPD' : "OPD's"}
                                                            </Badge>
                                                        </span>
                                                    )}
                                                    {isTodayDate && (
                                                        <span className={`absolute md:top-1.5 top-1 md:right-1.5 right-1 h-1.5 w-1.5 rounded-full ${isSelected ? 'bg-white' : 'bg-primary'}`} />
                                                    )}
                                                </div>
                                            </button>
                                        );
                                    }
                                }}
                            />
                        </div>

                        {/* Middle Column - Doctor OPD Schedule */}
                        <div className="lg:col-span-1">
                            <DoctorOpdSchedule
                                title="Doctor OPD Schedule"
                                date={selectedDate
                                    ? selectedDate.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })
                                    : 'Select a date'}
                                count={selectedDate ? getOPDCount(selectedDate) : 0}
                                countLabel="Slots"
                                emptyIcon={<Stethoscope className="h-8 w-8 mx-auto mb-2 opacity-30" />}
                                emptyMessage="No doctor OPD scheduled"
                                emptySubMessage="Select a date with OPD sessions"
                                OPDSlotsForSelectedDate={getOPDSlotsForDate(selectedDate)}
                                selectedSlot={selectedSlot}
                                onSlotClick={onSlotClick}
                            />
                        </div>


                        {/* Right Column - Booked Appointments */}
                        <div className="lg:col-span-1 space-y-3">

                            <Card className="border-border h-full py-0 flex flex-col">

                                {/* Header */}
                                <CardHeader className="bg-primary text-white rounded-t-lg py-2">
                                    <div className="flex justify-between items-center">
                                        <div>
                                            <CardTitle className="text-sm">Booked Appointments</CardTitle>
                                            {selectedSlot && (
                                                <p className="text-xs opacity-80">
                                                    {selectedSlot.time_range} • {filteredAppointments.length} Booked
                                                </p>
                                            )}
                                        </div>
                                        <Badge variant="secondary">
                                            {filteredAppointments.length} Patients
                                        </Badge>
                                    </div>
                                </CardHeader>

                                {filteredAppointments.length ? (
                                    filteredAppointments.map((appointment) => (
                                        <CardContent key={appointment.appointment_id}>
                                            <BookAppointments
                                                type="patient"
                                                title={
                                                    appointment.patient?.name ||
                                                    "Unknown Patient"
                                                }
                                                avatar={appointment.patient?.avatar || ""}
                                                time={(appointment as any).appointment_time_formatted || (appointment as any).appointment_time || (appointment as any).appointmentTime}
                                                appointmentType={
                                                    (appointment as any).consultation_type === "video"
                                                        ? "Video"
                                                        : "In-Person"
                                                }
                                                status={((appointment as any).status_label || (appointment as any).status) as any}
                                                onClick={() => {
                                                    if (appointment.appointment_id) {
                                                        router.push(`/appointments/${appointment.appointment_id}`);
                                                    }
                                                }}
                                            />
                                        </CardContent>
                                    ))
                                ) : (
                                    <div className="text-center py-10 border rounded-lg border-dashed text-muted-foreground">
                                        <p className="text-sm">
                                            {selectedSlot
                                                ? "No appointments booked for this slot"
                                                : "Select an OPD slot to view appointments"}
                                        </p>
                                    </div>
                                )}

                            </Card>
                        </div>

                    </div>

                </CardContent>

            </Card>
        </div>
    );
};

export default MySchedulesPage;