"use client";

import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

import { Badge } from "@/components/ui/badge";
import { FileText, Calendar, ArrowUpRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { getStatusColor } from "@/src/utils/getStatusColor";

export default function Reports({ appointment }: { appointment: any }) {
    const reports = appointment?.medical_reports || [];

    // ✅ EMPTY STATE
    if (!reports.length) {
        return (
            <Card className="rounded-xl sm:rounded-2xl border shadow-sm">
                <CardContent className="py-8 sm:py-12 text-center px-4">
                    <FileText className="mx-auto h-8 w-8 sm:h-10 sm:w-10 text-muted-foreground mb-3 sm:mb-4" />
                    <h3 className="text-base sm:text-lg font-semibold">
                        No medical reports available
                    </h3>
                    <p className="text-xs sm:text-sm text-muted-foreground mt-1">
                        Reports will appear here when uploaded
                    </p>
                </CardContent>
            </Card>
        );
    }

    // ✅ DATA AVAILABLE
    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 md:gap-5">
            {reports.map((report: any) => (
                <Card
                    key={report.id}
                    className="flex flex-col h-full overflow-hidden rounded-xl sm:rounded-2xl p-0"
                >
                    {/* Desktop Layout - Hidden on mobile */}
                    <div className="hidden sm:block">

                        <CardHeader className="p-4 pb-2">
                            <div className="flex justify-between items-center gap-2">
                                <CardTitle className="text-base flex items-center gap-2 flex-wrap">
                                    <span className="text-sm font-medium break-words whitespace-normal">{report.title}</span>
                                </CardTitle>
                                <Button
                                    size="sm"
                                    className="gap-2 cursor-pointer shrink-0"
                                    onClick={() => window.open(report.file_url, "_blank")}
                                >
                                    <ArrowUpRight className="h-4 w-4" />
                                    View
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent className="space-y-1 text-sm pt-0 pb-4">
                            <div className="flex items-center gap-2 text-muted-foreground">
                                <Calendar className="h-4 w-4" />
                                <span>{report.report_date_formatted}</span>
                            </div>
                            <p className="text-muted-foreground">
                                Doctor:{" "}
                                <span className="font-medium text-foreground">
                                    {report.doctor_name}
                                </span>
                            </p>
                            <Badge variant="outline" className="text-xs mt-1 bg-blue-100 text-blue-700 border border-blue-200 py-1.5 px-3 !rounded-md h-auto">
                                Uploaded by:{" "}
                                <span className="">
                                    {report.report_uploaded_by === "Patient" ? appointment?.patient?.name : appointment?.doctor?.name}
                                </span>
                            </Badge>
                        </CardContent>
                    </div>

                    {/* Mobile Layout - Visible only on mobile */}
                    <div className="sm:hidden flex flex-col h-full">
                        <CardHeader className="p-3 pb-2">
                            <div className="flex flex-wrap items-center gap-1.5">
                                <CardTitle className="text-sm font-semibold truncate flex-1">
                                    {report.title}
                                </CardTitle>
                                <Badge variant="outline" className="text-[9px] px-1.5">
                                    {report.type_label}
                                </Badge>
                                <Badge
                                    variant="outline"
                                    className={`${getStatusColor(
                                        "report",
                                        report.status
                                    )} text-[9px] font-medium px-1.5 py-0`}
                                >
                                    {report.status}
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent className="p-3 pt-0 space-y-1 text-xs flex-1">
                            <div className="flex items-center gap-1.5 text-muted-foreground">
                                <Calendar className="h-3 w-3" />
                                <span className="truncate">{report.report_date_formatted}</span>
                            </div>
                            <p className="text-muted-foreground">
                                Doctor:{" "}
                                <span className="font-medium text-foreground">
                                    {report.doctor_name}
                                </span>
                            </p>
                        </CardContent>

                        {/* Button at bottom on mobile */}
                        <div className="p-3 pt-0 mt-auto">
                            <Button
                                size="sm"
                                className="gap-1.5 cursor-pointer w-full text-xs h-8"
                                onClick={() => window.open(report.file_url, "_blank")}
                            >
                                <ArrowUpRight className="h-3 w-3" />
                                View
                            </Button>
                        </div>
                    </div>
                </Card>
            ))}
        </div>
    );
}