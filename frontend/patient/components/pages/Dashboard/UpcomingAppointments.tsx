import { CustomAvatar } from "@/components/custom/custom-avatar";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription } from "@/components/ui/card";
import { ArrowRight, Calendar, Clock, Video } from "lucide-react";
import Link from "next/link";

export interface AppointmentDoctor {
    specialty?: string;
    experience?: string;
    languages?: string[];
}

export interface Appointment {
    id: string | number;
    doctorId: string | number;
    doctorName: string;
    doctorImage: string;
    date: string;
    time: string;
    type: "video" | "in-person" | string;
    typeLabel: string;
    doctor?: AppointmentDoctor;
    joinUrl?: string;
}

interface UpcomingAppointmentsProps {
    appointments: Appointment[];
    onViewAll: () => void;
    onStartCall: (appointmentId: string | number) => void;
    onBookFirst: () => void;
}

function AppointmentCard({
    appointment,
    onStartCall,
    onViewAll,
    showViewAll,
}: {
    appointment: Appointment;
    onStartCall: (id: string | number) => void;
    onViewAll: () => void;
    showViewAll: boolean;
}) {

    console.log("doctore appoitment 2", appointment);

    const doctor = appointment.doctor;

    console.log("doctore appoitment", doctor);


    return (
        <Card className="overflow-hidden rounded-[5px] h-full border-0 bg-primary shadow-card-lg">
            <CardContent className="py-3.75 px-5">
                <div className="flex items-start gap-5">
                    {/* Doctor image */}
                    <div className="shrink-0">
                        <CustomAvatar
                            src={appointment.doctorImage}
                            radius="sm"
                            className="h-26 w-26 object-cover"
                        />
                    </div>

                    {/* Doctor details */}
                    <div className="flex-1 min-w-0">
                        <div className="flex flex-col gap-2 min-[480px]:flex-row min-[480px]:justify-between min-[480px]:items-center">

                            {/* Badge */}
                            <div className="order-1 min-[480px]:order-2 flex justify-start min-[480px]:justify-end shrink-0">
                                <Badge className="rounded-[3px] bg-white px-3 py-1 text-xs font-semibold text-green-600 hover:bg-white">
                                    Upcoming Session
                                </Badge>
                            </div>

                            {/* Doctor Name */}
                            <h3 className="order-2 min-[480px]:order-1 text-lg font-semibold text-white leading-tight break-words">
                                {appointment.doctorName}
                            </h3>

                        </div>
                        <p className="mt-2 text-sm font-medium text-white/95 break-words">
                            {doctor?.specialty || "Cardiology"}
                        </p>

                        <p className="mt-2 text-xs font-medium text-white/90 flex items-center gap-1 flex-wrap">
                            <span>Exp: {doctor?.experience || "14 years"}</span>
                            <span>•</span>
                            <span>
                                {doctor?.languages?.join(", ") || "English, Hindi, Punjabi"}
                            </span>
                        </p>

                        {/* Appointment meta */}
                        <div className="mt-3 flex flex-wrap items-center gap-4 text-white text-xs font-medium">
                            <div className="flex items-center gap-2 min-w-0">
                                <Calendar className="h-4 w-4 shrink-0" />
                                <span>{appointment.date}</span>
                            </div>

                            <div className="flex items-center gap-2 min-w-0">
                                <Clock className="h-4 w-4 shrink-0" />
                                <span>{appointment.time}</span>
                            </div>

                            <div className="flex items-center gap-2 min-w-0">
                                <Video className="h-4 w-4 shrink-0" />
                                <span>{appointment.typeLabel}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* CTA Button */}
                {appointment.type === "video" && (
                    <div className="mt-6">
                        <Button
                            onClick={() => {
                                console.log("appointment:", appointment);
                                window.open(
                                    `/start-consultation?room_url=${encodeURIComponent(appointment.joinUrl || "")}&appointment_id=${appointment.id}`,
                                    "_blank"
                                );
                            }}
                            className="md:h-8 h-9 w-full cursor-pointer rounded-[5px] bg-white text-sm md:text-xs font-semibold text-black hover:bg-white/95 flex items-center justify-center gap-2"
                        >
                            <Video className="h-4 w-4" />
                            Start Video Call
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export function UpcomingAppointments({
    appointments,
    onViewAll,
    onStartCall,
    onBookFirst,
}: UpcomingAppointmentsProps) {
    const handleViewAll = () => {
        window.open("/appointments", "_blank", "noopener,noreferrer");
        if (onViewAll) onViewAll();
    };

    // const appointment = {
    //   doctorName: "Dr. Emily Carter",
    //   doctorImage: "https://randomuser.me/api/portraits/women/44.jpg",
    //   date: "Fri, Apr 10",
    //   time: "07:00 PM",
    //   type: "video",
    //   typeLabel: "Video",
    //   doctor: {
    //     specialty: "Cardiology",
    //     experience: "14 years",
    //     languages: ["English", "Hindi"],
    //   },
    // };

    // No appointments
    if (!appointments || appointments.length === 0) {
        return (
            <Card className="rounded-[5px] border border-border/50 h-full flex flex-col justify-center text-center shadow-card-lg">
                <CardContent className="py-3.75 px-5 flex flex-col items-center">
                    <div className="h-13.75 w-14.5 bg-muted rounded-[6px] flex items-center justify-center">
                        <Calendar className="h-6.5 w-6.5 text-muted-foreground" />
                    </div>

                    <h3 className="text-lg font-semibold text-primary font-headline mt-4">
                        No Upcoming Sessions
                    </h3>

                    <p className="text-sm font-normal text-muted-foreground px-4 mt-2">
                        You don&apos;t have any appointments scheduled at the moment.
                    </p>

                    <Button
                        onClick={onBookFirst}
                        className="mt-5 bg-primary text-primary-foreground rounded-[5px] px-3 font-semibold py-4.5 shadow-sm hover:bg-primary/90 transition-all text-xs w-full sm:w-auto"
                    >
                        Book Your First Appointment
                    </Button>
                </CardContent>
            </Card>

            // <Card className="bg-[#1e5cc8] border-0 rounded-xl overflow-hidden shadow-none">

            //   {/* Top Section */}
            //   <CardDescription className="px-5 flex flex-col gap-4">

            //     <div className="flex justify-between items-center">

            //     </div>

            //     {/* Doctor Info */}
            //     <div className="flex gap-3 items-center">
            //       <CustomAvatar
            //         src={appointment.doctorImage}
            //         className="w-24 h-24 rounded-lg border border-white/30"
            //       />

            //       <div>
            //         <div className="flex justify-center items-center gap-2">
            //           <h3 className="text-white text-lg font-semibold leading-tight">
            //             {appointment.doctorName}
            //           </h3>
            //           <Badge className="bg-white/20 text-white text-[10px] px-2 py-1 rounded-full border-0">
            //             Upcoming Session
            //           </Badge>
            //         </div>
            //         <p className="text-white/80 text-sm">
            //           {appointment.doctor.specialty}
            //         </p>
            //         <p className="text-white/60 text-xs mt-1">
            //           {appointment.doctor.experience} •{" "}
            //           {appointment.doctor.languages.join(", ")}
            //         </p>
            //         <div className="flex text-white text-xs mt-2 gap-5">

            //           <div className="flex items-center gap-1">
            //             <Calendar className="w-3 h-3" />
            //             <span>{appointment.date}</span>
            //           </div>

            //           <div className="flex items-center gap-1">
            //             <Clock className="w-3 h-3" />
            //             <span>{appointment.time}</span>
            //           </div>

            //           <div className="flex items-center gap-1">
            //             <Video className="w-3 h-3" />
            //             <span>{appointment.typeLabel}</span>
            //           </div>
            //         </div>
            //       </div>
            //     </div>

            //   </CardDescription>

            //   {/* Bottom Section */}
            //   <CardContent className="bg-[#1b56bd] p-4 flex flex-col gap-4">

            //     {/* Info Row */}


            //     {/* EXACT BUTTON */}
            //     <Button className="w-full bg-white text-black text-sm font-medium py-2 rounded-md hover:bg-gray-100 shadow-none">
            //       <Video className="w-4 h-4 mr-2" />
            //       Start Video Call
            //     </Button>

            //   </CardContent>
            // </Card>
        );
    }

    // Show only one upcoming appointment
    return (
        <AppointmentCard
            appointment={appointments[0]}
            onStartCall={onStartCall}
            onViewAll={handleViewAll}
            showViewAll={true}
        />
    );
}