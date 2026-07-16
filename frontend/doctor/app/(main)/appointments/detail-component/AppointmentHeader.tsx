"use client";

import {
    Card,
    CardContent,
} from "@/components/ui/card";
import { toast } from "sonner";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Calendar, CheckCircle, Dot, Mail, Phone, Video } from "lucide-react";
import { getStatusColor } from "@/src/utils/getStatusColor";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { cancelAppointment } from "@/mutations/mange-appoitment";
import CustomDialog from "@/components/custom/Dialogboxs";

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

    console.log("all data " ,appointment);
    

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
        <Card className="rounded-md !border-light-gray shadow-[0px_2px_4px_0px_#0000001A] p-3 lg:p-5">
            <CardContent className="p-0">
                <div className="flex flex-col w-full gap-3 sm:gap-4">

                    {/* Desktop Layout - Same as original */}
                    <div className="hidden sm:flex sm:justify-between w-full gap-4">

                        {/* Patient Info */}
                        <div className="flex items-center gap-3">
                            <Avatar className="h-24 w-24">
                                <AvatarImage src={patient?.avatar} />
                                <AvatarFallback className="bg-primary/10 text-primary font-semibold">
                                    {getInitials(patient?.name)}
                                </AvatarFallback>
                            </Avatar>

                            <div className="flex flex-col gap-1">

                                <div className="flex items-center gap-1">
                                    <p className="mt-0.5 flex items-center font-bold text-primary text-xs font-semibold">
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

                                <div className="flex items-center gap-1">
                                    <h2 className="text-[#1F1E1E] text-2xl font-bold">
                                        {patient?.name || "Unknown Patient"}
                                    </h2>
                                    <span>
                                        ({patient?.age_formatted || "N/A"},{" "}
                                        {patient?.gender_formatted || "N/A"})
                                    </span>
                                </div>

                                <div className="flex items-center flex-wrap gap-x-2.5 text-sm text-muted-foreground">
                                    <p className="text-sm font-medium flex items-center gap-1 px-2.5 py-1.5 rounded-md bg-[#F5F6F8]">
                                        <Mail className="h-3 w-3" /> {patient?.email || "Not provided"}
                                    </p>
                                    <p className="text-sm font-medium flex items-center gap-1 px-2.5 py-1.5 rounded-md bg-[#F5F6F8]">
                                        <Phone className="h-3 w-3" /> {patient?.phone || "Not provided"}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Date/Time, Badges, Cancel Button */}
                        <div className="flex flex-col items-end justify-between flex-wrap gap-2">

                            <div className="flex flex-col items-end gap-y-2.5">
                                <Badge className={`${getStatusColor("appointment", localStatus)} gap-1 py-1.5 px-3 !rounded-md h-auto`}>
                                    {appointment?.status_label || "Completed"}
                                </Badge>

                                <Badge variant="outline" className="gap-1 border-[#E7E8EB] py-1.5 px-2 !rounded-md h-auto bg-[#F5F6F8]">
                                    <Video className="h-3 w-3" />
                                    {schedule?.consultation_type_label || "Video Consultation"}
                                </Badge>
                            </div>

                            {callNow && joinUrl && (
                                <Button
                                    variant="default"
                                    onClick={() => window.open(`/start-consultation?room_url=${joinUrl}&appointment_id=${appointment.appointment_id}`, "_blank")}
                                    disabled={!joinUrl}
                                    className="h-auto py-2 px-4"
                                >
                                    Join Now
                                </Button>
                            )}
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