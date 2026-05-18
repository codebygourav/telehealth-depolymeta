"use client";

import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    Calendar,
    Clock,
    Video,
    Phone,
    MapPin,
    PhoneCall,
    PhoneCallIcon,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { useParams, useRouter } from "next/navigation";
import { getStatusColor } from "@/src/utils/getStatusColor";
import { useState } from "react";
import { RescheduleAppointmentDialog } from "./Reshedule-dialogbox";
import CustomDialog from "@/components/custom/Dialogboxs";

interface AppointmentCardProps {
    appointment: any;
    variant?: "today" | "upcoming" | "past" | "all";
    onCallNow?: () => void;
}


// ✅ Consultation Icon
const getConsultationIcon = (type: string) => {
    switch (type) {
        case "video":
            return <Video className="h-3.5 w-3.5" />;
        case "phone":
            return <Phone className="h-3.5 w-3.5" />;
        case "in-person":
        case "clinic":
            return <MapPin className="h-3.5 w-3.5" />;
        default:
            return <Video className="h-3.5 w-3.5" />;
    }
};

// ✅ Initials fallback
const getInitials = (name: string) => {
    if (!name) return "?";
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
};

export default function AppointmentCard({
    appointment,
    variant = "all",
    onCallNow,
}: AppointmentCardProps) {

    const [openRescheduleDialog, setOpenRescheduleDialog] = useState(false);
    const [customDialogOpen, setCustomDialogOpen] = useState(false);
    const [dialogData, setDialogData] = useState<any>(null);
    const joinUrl = appointment?.join_url;
    const showCallNow = appointment.call_now === true;
    const router = useRouter();

    // button show hide in rescheduled", "failed", "completed base
    const shouldHideReschedule =
        variant === "past" ||
        ["failed", "cancelled", "rescheduled", "completed"].includes(appointment.status);

    return (
        <>
            <Card className="group rounded-md !border-light-gray shadow-[0px_2px_4px_0px_#0000001A] h-full lg:p-5 p-3">
                <CardContent className="p-0">

                    {/* 🔹 Header */}
                    <div className="flex gap-2">

                        {/* Avatar */}
                        <Avatar className="h-14 w-14 rounded-full">
                            <AvatarImage
                                src={appointment.patient?.avatar || appointment?.patient_image || ""}
                                alt={appointment.patient?.name || appointment?.patient_name || "Patient"}
                            />
                            <AvatarFallback className="bg-primary/10 text-primary font-medium text-xs sm:text-sm">
                                {getInitials(appointment.patient?.name || appointment?.patient_name)}
                            </AvatarFallback>
                        </Avatar>

                        {/* Patient Info */}
                        <div className="flex-1 min-w-0">
                            <div className="flex flex-row sm:items-start justify-between gap-1.5 sm:gap-2">

                                {/* Left - Patient Details */}
                                <div className="flex-1 min-w-0">
                                    <h3 className="text-sm sm:text-base font-semibold truncate">
                                        {appointment.patient?.name || appointment?.patient_name || "Unknown Patient"}
                                    </h3>

                                    <div className="flex items-center gap-1.5 mt-1 flex-wrap">
                                        <Badge
                                            variant="secondary"
                                            className="text-[#4D4D4D] text-xs font-semibold py-1 px-2.5 h-auto rounded"
                                        >
                                            {getConsultationIcon(appointment.consultation_type)}
                                            <span>
                                                {appointment.consultation_type === "clinic"
                                                    ? "In-Person"
                                                    : appointment.consultation_type_label?.split(" ")[0] ||
                                                    "Video"}
                                            </span>
                                        </Badge>
                                    </div>
                                </div>

                                {/* Right - Join Now Badge (Top Right) */}
                                {showCallNow ? (
                                    <Badge
                                        className="bg-success text-success-foreground hover:opacity-90 cursor-pointer shrink-0 gap-1 px-2 sm:px-2.5 py-1 text-[10px] sm:text-xs font-medium whitespace-nowrap"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            onCallNow?.();
                                        }}
                                    >
                                        <PhoneCall className="h-2.5 w-2.5 sm:h-3 sm:w-3" />
                                        <span className="hidden xs:inline">Join Now</span>
                                        <span className="xs:hidden">Join</span>
                                    </Badge>
                                ) :
                                    (
                                        <Badge
                                            className={`${getStatusColor(
                                                "appointment",
                                                appointment.status
                                            )} text-[10px] sm:text-xs font-medium px-3 py-1.5 whitespace-nowrap shrink-0 self-start sm:self-auto rounded h-auto`}
                                        >
                                            {appointment.status_label || appointment.status}
                                        </Badge>
                                    )}
                            </div>
                        </div>
                    </div>

                    {/* Date & Time */}
                    <div className="bg-[#F5F6F8] rounded-lg p-5 mt-5">

                        <div className="flex lg:flex-row flex-col gap-5 lg:items-center items-start justify-between relative">

                            <div className="flex items-center gap-x-3.5">
                                <div>
                                    <Calendar color='#4D4D4D' size={14} />
                                </div>
                                <div>
                                    <p className="text-[#4D4D4D] text-xs font-medium">
                                        Date
                                    </p>
                                    <p className="text-[#4D4D4D] font-semibold text-sm">
                                        {appointment.appointment_date_formatted ||
                                            appointment.appointment_date || appointment.date}
                                    </p>
                                </div>
                            </div>

                            <div className='absolute left-1/2 top-0 w-px h-10 mx-auto bg-[#E7E8EB] lg:block hidden'></div>

                            <div className="flex items-center gap-x-3.5">
                                <div>
                                    <Clock color='#4D4D4D' size={14} />
                                </div>
                                <div>
                                    <p className="text-[#4D4D4D] text-xs font-medium">
                                        Time
                                    </p>
                                    <p className="text-[#4D4D4D] font-semibold text-sm">
                                        {appointment.appointment_time_formatted ||
                                            appointment.appointment_time || appointment.time}
                                        {appointment.appointment_end_time_formatted &&
                                            ` - ${appointment.appointment_end_time_formatted}`}
                                    </p>
                                </div>
                            </div>

                        </div>

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row gap-2 sm:gap-3 mt-4 sm:mt-5">

                            {/* Always show View button */}
                            <Button
                                className="flex-1 cursor-pointer py-2.5 h-auto font-semibold rounded-md"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    router.push(`/appointments/${appointment.appointment_id || appointment.id}`);
                                }}
                            >
                                View
                            </Button>

                            {/* Show Join Now if video consultation is joinable */}
                            {appointment.video_consultation?.can_join || appointment.call_now ? (
                                <Button
                                    variant="outline"
                                    className="flex-1 h-auto border-primary cursor-pointer text-xs sm:text-sm gap-1.5 flex items-center justify-center"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        window.open(`/start-consultation?room_url=${joinUrl}&appointment_id=${appointment.appointment_id || appointment.id}`, "_blank");
                                    }}
                                >
                                    <PhoneCallIcon className="h-3 w-3 sm:h-3.5 sm:w-3.5" />
                                    <span className="hidden xs:inline">Join Now</span>
                                    <span className="xs:hidden">Join</span>
                                </Button>
                            ) : (
                                // Else show Reschedule if allowed
                                !shouldHideReschedule && (
                                    <Button
                                        className="flex-1 cursor-pointer py-2.5 h-auto font-semibold rounded-md border-[#4D4D4D]"
                                        variant="outline"
                                        onClick={() => setOpenRescheduleDialog(true)}
                                    >
                                        Reschedule
                                    </Button>
                                )
                            )}
                        </div>

                    </div>

                </CardContent>
            </Card>

            <RescheduleAppointmentDialog
                open={openRescheduleDialog}
                onOpenChange={setOpenRescheduleDialog}
                appointmentId={appointment.appointment_id || appointment.id}
                setCustomDialogOpen={setCustomDialogOpen}
                setDialogData={setDialogData}
            />

            <CustomDialog
                open={customDialogOpen}
                onClose={() => {
                    setCustomDialogOpen(false);
                    setDialogData(null);
                }}
                type={dialogData?.title === "Validation Error" ? "danger" : "success"}
                title={dialogData?.title || ""}
                description={dialogData?.description || ""}
                confirmText="OK"
                onConfirm={() => {
                    setCustomDialogOpen(false);
                    setDialogData(null);
                }}
            />

        </>
    );
}