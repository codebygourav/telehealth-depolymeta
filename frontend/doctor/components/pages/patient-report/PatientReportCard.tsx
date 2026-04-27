"use client";

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    FileCheck,
    User,
    Droplet,
    Activity,
    Heart,
    FileText,
} from "lucide-react";

interface PatientReportCardProps {
    patient: any;
    onViewReports: (patient: any) => void;
}


interface Report {
    id: string; 
    report_type: string;
    report_name: string;
    report_date_formatted?: string; 
    uploaded_at?: string; 
}

const getInitials = (name?: string | null) => {
    if (!name) return "NA";
    return name
        .split(" ")
        .filter(Boolean)
        .map((word) => word[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
};

const getReportTypeIcon = (type?: string | null) => {
    const normalized = (type || "").toLowerCase();

    if (
        normalized.includes("lab") ||
        normalized.includes("blood") ||
        normalized.includes("test")
    ) {
        return <Droplet className="h-3 w-3 sm:h-4 sm:w-4 text-red-500" />;
    }

    if (
        normalized.includes("radiology") ||
        normalized.includes("x-ray") ||
        normalized.includes("scan") ||
        normalized.includes("mri")
    ) {
        return <Activity className="h-3 w-3 sm:h-4 sm:w-4 text-blue-500" />;
    }

    if (normalized.includes("card") || normalized.includes("heart")) {
        return <Heart className="h-3 w-3 sm:h-4 sm:w-4 text-pink-500" />;
    }

    return <FileText className="h-3 w-3 sm:h-4 sm:w-4 text-primary" />;
};

export default function PatientReportCard({
    patient,
    onViewReports,
}: PatientReportCardProps) {
    const reports = Array.isArray(patient.reports) ? patient.reports : [];

    return (
        <Card className="border-border hover:shadow-lg transition-all flex flex-col h-full">
            <CardHeader className="pb-2 px-4 sm:px-5 pt-4 sm:pt-5">
                <div className="flex justify-between items-start gap-2 sm:gap-3">
                    <div className="flex items-center gap-2 sm:gap-3 min-w-0">
                        <Avatar className="h-10 w-10 sm:h-12 sm:w-12 border-2 border-primary/20 shrink-0">
                            <AvatarImage
                                src={patient.avatar || ""}
                                alt={patient.name || ""}
                            />
                            <AvatarFallback className="bg-primary/10 text-primary text-xs sm:text-sm">
                                {getInitials(patient.name)}
                            </AvatarFallback>
                        </Avatar>

                        <div className="min-w-0">
                            <CardTitle className="text-sm sm:text-base md:text-lg truncate">
                                {patient.name}
                            </CardTitle>
                            <CardDescription className="text-xs sm:text-sm truncate">
                                {patient.patient_id}
                            </CardDescription>
                        </div>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="space-y-3 sm:space-y-4 flex-1 px-4 sm:px-5">
                <div className="flex items-center gap-2 text-xs sm:text-sm text-muted-foreground">
                    <User className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                    <span>Patient Reports Summary</span>
                </div>

                <div className="pt-1 sm:pt-2">
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-[10px] sm:text-xs font-medium text-muted-foreground">
                            Recent Reports
                        </p>
                        <Badge variant="outline" className="text-[10px] sm:text-xs">
                            {patient.total_reports_count} Total
                        </Badge>
                    </div>

                    <div className="space-y-1.5 sm:space-y-2">
                        {reports.slice(0, 2).map((report: Report) => (
                            <div
                                key={report.id}
                                className="flex items-center gap-1.5 sm:gap-2 text-xs bg-accent/30 p-1.5 sm:p-2 rounded"
                            >
                                {getReportTypeIcon(report.report_type)}
                                <div className="flex-1 min-w-0">
                                    <p className="font-medium text-[10px] sm:text-xs truncate">
                                        {report.report_name}
                                    </p>
                                    <p className="text-[9px] sm:text-[10px] text-muted-foreground truncate">
                                        {report.report_date_formatted || report.uploaded_at}
                                    </p>
                                </div>
                            </div>
                        ))}

                        {reports.length > 2 && (
                            <p className="text-[9px] sm:text-xs text-muted-foreground text-center">
                                +{reports.length - 2} more reports
                            </p>
                        )}
                    </div>
                </div>
            </CardContent>

            <div className="mt-auto px-4 sm:px-5 pb-4 sm:pb-5">
                <Button
                    className="w-full gap-2 h-9 sm:h-10 text-xs sm:text-sm"
                    onClick={() => onViewReports(patient)}
                >
                    <FileCheck className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                    Click to view reports
                </Button>
            </div>
        </Card>
    );
}