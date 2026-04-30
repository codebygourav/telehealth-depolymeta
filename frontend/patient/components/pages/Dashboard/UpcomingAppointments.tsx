import { Avatar, AvatarFallback, AvatarImage, Badge, Button, Card, CardContent, Separator } from "@/components/ui";
import type { HomeScreenAppointment } from "@/types/home-screen";
import { Calendar, Clock3, type LucideIcon, UserRound, Video } from "lucide-react";

interface UpcomingAppointmentsProps {
    appointments: HomeScreenAppointment[];
    onViewAll: () => void;
    onStartCall: (appointmentId: string | number) => void;
    onBookFirst: () => void;
}

function AppointmentMetaItem({
    icon: Icon,
    label,
    value,
}: {
    icon: LucideIcon;
    label: string;
    value: string;
}) {
    return (
        <div className="flex items-start gap-3">
            <div className="flex size-8.5 shrink-0 items-center justify-center global-radius bg-white text-foreground">
                <Icon className="size-4" />
            </div>
            <div>
                <p className="text-xs text-white/100 font-medium">{label}</p>
                <p className="text-md font-semibold text-white">{value}</p>
            </div>
        </div>
    );
}

function AppointmentCard({
    appointment,
    onStartCall,
}: {
    appointment: HomeScreenAppointment;
    onStartCall: (id: string | number) => void;
}) {
    const doctor = appointment.doctor;

    return (
        <Card className="h-full overflow-hidden rounded-lg primary-card-shadow p-0">
            <CardContent className="flex h-full flex-col justify-between p-5 sm:p-6">
                <div className="flex flex-1 flex-col gap-5 justify-between sm-justify-between">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="flex items-center gap-4 sm:gap-5">
                            <Avatar className="h-[100px] w-[100px] global-radius shadow-lg after:hidden sm:h-[108px] sm:w-[108px]">
                                <AvatarImage
                                    src={appointment.doctorImage}
                                    alt={appointment.doctorName}
                                    className="global-radius object-cover"
                                />
                                <AvatarFallback className="global-radius bg-white/15 text-white">
                                    <UserRound className="size-8" />
                                </AvatarFallback>
                            </Avatar>

                            <div className="min-w-0 pt-1">
                                <h3 className="text-2xl leading-tight font-semibold text-white sm:text-2xl">
                                    {appointment.doctorName}
                                </h3>
                                <p className="mt-2 text-lg font-semibold text-white/100">
                                    {doctor?.specialty || "Cardiology"}
                                </p>
                                <p className="mt-1.5 text-base font-semibold text-white/85 sm:text-base">
                                    Exp: {doctor?.experience || "14 years"}
                                    <span className="px-1.5">•</span>
                                    {doctor?.languages?.join(", ") || "English, Hindi, Punjabi"}
                                </p>
                            </div>
                        </div>

                        <Badge className="w-fit global-radius bg-white px-3 py-3 text-sm font-medium text-green-600 hover:bg-white">
                            Upcoming Session
                        </Badge>
                    </div>

                    <div className="grid gap-4  sm:grid-cols-3 sm:gap-6 ">
                        <AppointmentMetaItem icon={Calendar} label="Date" value={appointment.date} />
                        <AppointmentMetaItem icon={Clock3} label="Time" value={appointment.time} />
                        <AppointmentMetaItem icon={Video} label="Type" value={appointment.typeLabel} />
                    </div>
                </div>

                <Separator className="my-3 bg-transparent" />

                {appointment.type === "video" && (
                    <Button
                        onClick={() => {
                            onStartCall(appointment.id);
                            window.open(
                                `/start-consultation?room_url=${encodeURIComponent(appointment.joinUrl || "")}&appointment_id=${appointment.id}`,
                                "_blank"
                            );
                        }}
                        className="h-11 w-full rounded-md bg-white text-base font-semibold text-black hover:bg-white/95"
                    >
                        <Video className="mr-2 size-4" />
                        Start Video Call
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}

export function UpcomingAppointments({
    appointments,
    onStartCall,
    onBookFirst,
}: UpcomingAppointmentsProps) {
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
        />
    );
}
