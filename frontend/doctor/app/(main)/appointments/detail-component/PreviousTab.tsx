"use client";

import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { useRouter } from "next/navigation";
import { Badge } from "@/components/ui/badge";
import { Calendar, Clock, Video, MapPin, ArrowUpRight } from "lucide-react";
import { Button } from "@/components/ui";
import { getStatusColor } from "@/src/utils/getStatusColor";

export default function PreviousTab({ appointment }: { appointment: any }) {

    const previous = appointment?.previous_appointments || [];
    const router = useRouter();

    // ✅ EMPTY STATE
    if (!previous.length) {
        return (
            <Card className="rounded-2xl border shadow-sm">
                <CardContent className="py-12 text-center">
                    <Calendar className="mx-auto h-10 w-10 text-primary mb-4" />

                    <h3 className="text-lg font-semibold">
                        No previous appointments
                    </h3>

                    <p className="text-sm text-muted-foreground mt-1">
                        Previous consultations will appear here
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="grid md:grid-cols-2 xl:grid-cols-3 grid-cols-1 gap-5">
            {previous.map((item: any) => (
                <Card
                    key={item.id}
                    className="rounded-md transition"
                >
                    <CardHeader className="pb-2 flex justify-between items-center">

                        <CardTitle className="text-base flex items-center gap-2">
                            <span>{item.notes}</span>
                            <Badge
                                className={`${getStatusColor(
                                    "appointment",
                                    item.status
                                )}`}
                            >
                                {item.status_label}
                            </Badge>
                        </CardTitle>

                        <Button
                            size="sm"
                            className="gap-2 cursor-pointer"
                            onClick={() => router.push(`/appointments/${item.id}`)}
                        >
                            <ArrowUpRight className="h-4 w-4" />
                            View
                        </Button>

                    </CardHeader>

                    <CardContent className="space-y-3 text-sm">

                        {/* Date */}
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <Calendar className="h-4 w-4 text-primary" />
                            <span>{item.date}</span>
                        </div>

                        {/* Time */}
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <Clock className="h-4 w-4 text-primary" />
                            <span>{item.time}</span>
                        </div>

                        {/* Type */}
                        <div className="flex items-center gap-2 text-muted-foreground">
                            {item.consultation_type === "video" ? (
                                <Video className="h-4 w-4 text-primary" />
                            ) : (
                                <MapPin className="h-4 w-4 text-primary" />
                            )}
                            <span>{item.consultation_type_label}</span>
                        </div>

                    </CardContent>
                </Card>
            ))}
        </div>
    );
}