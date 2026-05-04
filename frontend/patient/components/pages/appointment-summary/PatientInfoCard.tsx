"use client";

import { Card, CardContent } from "@/components/ui/card";
import { Star, Stethoscope, User } from "lucide-react";
import type { AppointmentPatient } from "@/types/appointment-summary";

interface PatientInfoCardProps {
    patient: AppointmentPatient;
}

const PatientInfoCard = ({ patient }: PatientInfoCardProps) => {
    return (
        <Card className="global-radius-10 g-border p-0">
            <CardContent className="p-0">
                <div className="p-4 sm:p-5 md:p-6">
                    <div className="flex items-center mb-5 gap-2">
                        <User className="w-5 h-5 text-primary" />
                        <span className="font-semibold text-lg">Patient Info</span>
                    </div>
                    <div className="grid grid-cols-[140px_1fr] gap-y-3 text-sm leading-snug">

                        <div className="text-muted-foreground">Name</div>
                        <div className="text-right font-semibold break-all">{patient.name}</div>

                        <div className="text-muted-foreground">Age</div>
                        <div className="text-right">{patient.age_formatted} Years</div>

                        <div className="text-muted-foreground">Gender</div>
                        <div className="text-right">{patient.gender_formatted}</div>

                        <div className="text-muted-foreground">Blood Group</div>
                        <div className="text-right">{patient.blood_group || "--"}</div>

                        <div className="text-muted-foreground">Phone</div>
                        <div className="text-right">{patient.phone || "--"}</div>

                        <div className="text-muted-foreground">Email</div>
                        <div className="text-right truncate  leading-tight"
                            style={{ wordBreak: "break-all" }}>{patient.email || "--"}</div>
                    </div>
                </div>
            </CardContent>
        </Card>


    );
};

export default PatientInfoCard;