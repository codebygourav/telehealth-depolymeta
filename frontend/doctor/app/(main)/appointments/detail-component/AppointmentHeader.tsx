"use client";

import {
    Card,
    CardContent,
} from "@/components/ui/card";
import { toast } from "sonner";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Calendar, CheckCircle, Dot, Mail, Phone, Trash2, Video } from "lucide-react";
import { getStatusColor } from "@/src/utils/getStatusColor";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { cancelAppointment } from "@/mutations/mange-appoitment";
import CustomDialog from "@/components/custom/Dialogboxs";
import { Separator } from "@/components/ui/separator";

const getInitials = (name: string) => {
    if (!name) return "?";
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
};

export default function AppointmentHeader({ appointment }: { appointment: any }) {

    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [localStatus, setLocalStatus] = useState(appointment?.status);
    const patient = appointment?.patient || {};
    const schedule = appointment?.schedule || {};
    const [successOpen, setSuccessOpen] = useState(false);

    const handleCancelAppointment = async () => {
        try {
            setLoading(true);
            const appointmentId = appointment?.appointment_id || appointment?.id;

            const res = await cancelAppointment(appointmentId);

            setLocalStatus("cancelled");
            setOpen(false);
            setSuccessOpen(true);
        } catch (error: any) {
            const errorMessage =
                error?.response?.data?.errors?.message ||
                error?.response?.data?.message ||
                "Something went wrong";

            toast.error(errorMessage);
        } finally {
            setLoading(false);
        }
    };

    const joinUrl = appointment?.join_url || "";
    const callNow = appointment?.call_now || "";

    return (
        <Card className="shadow-sm rounded-xl sm:rounded-2xl border overflow-hidden">
            <CardContent>
                <div className="flex flex-col w-full gap-3 sm:gap-4">
                    {/* Desktop Layout - Same as original */}
                    <div className="hidden sm:flex sm:flex-col w-full gap-4">
                        {/* Top Row - Date/Time, Badges, Cancel Button */}
                        <div className="flex items-center justify-between flex-wrap gap-4">
                            <div className="flex items-center text-base md:text-lg flex-wrap gap-2">
                                <div className="flex items-center gap-1">
                                    <p className="mt-0.5 flex items-center font-bold">
                                        {schedule?.date_format || appointment?.appointment_date_format}
                                        <span className="opacity-50 px-1"> | </span>
                                        {schedule?.time_formatted || appointment?.appointment_time_formatted}
                                        {appointment?.appointment_end_time_formatted &&
                                            ` - ${appointment.appointment_end_time_formatted}`}
                                        <span className="opacity-50">
                                            <Dot className="h-5 md:h-7" />
                                        </span>
                                        {schedule?.day_format || appointment?.appointment_date_format}
                                    </p>
                                </div>

                                <Badge className={`${getStatusColor("appointment", localStatus)} gap-1`}>
                                    {appointment?.status_label || "Completed"}
                                </Badge>

                                <Badge variant="outline" className="gap-1">
                                    <Video className="h-3 w-3" />
                                    {schedule?.consultation_type_label || "Video Consultation"}
                                </Badge>
                            </div>

                            <div>
                                {callNow && joinUrl && (
                                    <Button
                                        variant="default"
                                        onClick={() => window.open(`/start-consultation?room_url=${joinUrl}&appointment_id=${appointment.appointment_id}`, "_blank")}
                                        disabled={!joinUrl}
                                    >
                                        Join Now
                                    </Button>
                                )}
                            </div>
                        </div>

                        <Separator className="w-full opacity-50" />

                        {/* Patient Info */}
                        <div className="flex items-center gap-3">
                            <Avatar className="h-14 w-14 border-2 border-primary/10">
                                <AvatarImage src={patient?.avatar} />
                                <AvatarFallback className="bg-primary/10 text-primary font-semibold">
                                    {getInitials(patient?.name)}
                                </AvatarFallback>
                            </Avatar>

                            <div className="flex flex-col gap-1">
                                <div className="flex gap-1 flex-wrap">
                                    <h2 className="text-base leading-none">
                                        {patient?.name || "Unknown Patient"}
                                    </h2>
                                    <span>
                                        ({patient?.age_formatted || "N/A"},{" "}
                                        {patient?.gender_formatted || "N/A"})
                                    </span>
                                </div>
                                <div className="flex items-center flex-wrap gap-1 text-sm text-muted-foreground">
                                    <p className="text-sm font-medium flex items-center gap-1">
                                        <Phone className="h-3 w-3" /> {patient?.phone || "Not provided"}
                                    </p>
                                    <span className="text-muted-foreground/50">|</span>
                                    <p className="text-sm font-medium flex items-center gap-1">
                                        <Mail className="h-3 w-3" /> {patient?.email || "Not provided"}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Mobile Layout - Responsive Changes */}
                    <div className="flex flex-col gap-3 sm:hidden">
                        {/* Patient Profile - Top */}
                        <div className="flex items-center gap-3">
                            <Avatar className="h-12 w-12 border-2 border-primary/10 shrink-0">
                                <AvatarImage src={patient?.avatar} />
                                <AvatarFallback className="bg-primary/10 text-primary font-semibold text-sm">
                                    {getInitials(patient?.name)}
                                </AvatarFallback>
                            </Avatar>

                            <div className="flex-1 min-w-0">
                                <div className="flex flex-wrap items-baseline gap-x-1 gap-y-0.5">
                                    <h2 className="text-sm font-semibold truncate">
                                        {patient?.name || "Unknown Patient"}
                                    </h2>
                                    <span className="text-[10px] text-muted-foreground">
                                        ({patient?.age_formatted || "N/A"}, {patient?.gender_formatted || "N/A"})
                                    </span>
                                </div>
                                <div className="mt-1 space-y-0.5">
                                    <p className="flex items-center gap-1 text-[10px] text-muted-foreground">
                                        <Phone className="h-2.5 w-2.5" />
                                        <span className="truncate">{patient?.phone || "Not provided"}</span>
                                    </p>
                                    <p className="flex items-center gap-1 text-[10px] text-muted-foreground">
                                        <Mail className="h-2.5 w-2.5" />
                                        <span className="truncate">{patient?.email || "Not provided"}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Separator className="w-full opacity-50" />

                        {/* Date & Time - Mobile */}
                        <div className="flex flex-wrap items-center gap-1">
                            <Calendar className="h-3 w-3 text-muted-foreground" />
                            <span className="text-[11px] font-medium">
                                {schedule?.date_format || appointment?.appointment_date_format}
                            </span>
                            <span className="text-[11px] text-muted-foreground">|</span>
                            <span className="text-[11px] font-medium">
                                {schedule?.time_formatted || appointment?.appointment_time_formatted}
                                {appointment?.appointment_end_time_formatted &&
                                    ` - ${appointment.appointment_end_time_formatted}`}
                            </span>
                            <span className="text-[11px] text-muted-foreground">•</span>
                            <span className="text-[11px] font-medium">
                                {schedule?.day_format || appointment?.appointment_date_format}
                            </span>
                        </div>

                        {/* Badges - Mobile */}
                        <div className="flex flex-wrap items-center gap-1.5">
                            <Badge className={`${getStatusColor("appointment", localStatus)} gap-1 text-[9px] px-1.5 py-0`}>
                                {appointment?.status_label || "Completed"}
                            </Badge>
                            <Badge variant="outline" className="gap-1 text-[9px] px-1.5 py-0">
                                <Video className="h-2 w-2" />
                                {schedule?.consultation_type_label || "Video"}
                            </Badge>
                        </div>

                        {/* Join Now Button - Bottom */}
                        {!successOpen &&
                            ["confirmed", "rescheduled"].includes(appointment?.status) &&
                            joinUrl && (
                                <Button
                                    variant="default"
                                    className="w-full h-8 text-xs mt-1"
                                    onClick={() => window.open(joinUrl, "_blank")}
                                >
                                    Join Now
                                </Button>
                            )}
                    </div>
                </div>

                {/* Dialogs */}
                <CustomDialog
                    open={open}
                    onClose={() => setOpen(false)}
                    title="Cancel Appointment"
                    description="Are you sure you would like to do this?"
                    confirmText={loading ? "Cancelling..." : "Yes, Cancel Appointment"}
                    cancelText="No, Keep Appointment"
                    onConfirm={handleCancelAppointment}
                    loading={loading}
                    type="danger"
                />

                <CustomDialog
                    open={successOpen}
                    onClose={() => setSuccessOpen(false)}
                    icon={<CheckCircle className="h-5 w-5 sm:h-6 sm:w-6 text-green-600" />}
                    title="Appointment Cancelled"
                    description="Your appointment has been successfully cancelled."
                    confirmText="OK"
                    onConfirm={() => setSuccessOpen(false)}
                    type="success"
                />
            </CardContent>
        </Card>
    );
}