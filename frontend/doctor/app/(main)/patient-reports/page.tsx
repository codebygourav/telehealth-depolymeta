"use client";

import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    CardFooter,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    Search,
    Download,
    ArrowLeft,
    Calendar,
    FileText,
    FileCheck,
    Eye,
    Activity,
    Heart,
    X,
    Droplet,
    User,
} from "lucide-react";

import { usePatientReports } from "@/queries/usePatientReports";
import type {
    PatientReportItem,
    PatientReportPatient,
} from "@/types/patient-reports";
import PaginationControls from "@/components/pagination/PaginationControls";
import PatientReportCard from "@/components/pages/patient-report/PatientReportCard";
// Import the new component

export default function PatientReportsPage() {
    const router = useRouter();

    const [searchQuery, setSearchQuery] = useState("");
    const [selectedPatient, setSelectedPatient] =
        useState<PatientReportPatient | null>(null);
    const [page, setPage] = useState(1);

    // Main list query — no filter
    const { data, isLoading, isError, error } = usePatientReports({
        page,
        per_page: 10,
    });

    // Modal query — fires with filter=all only when a patient is selected
    const { data: modalData, isLoading: modalLoading } = usePatientReports({
        page: 1,
        per_page: 100,
        filter: "all",
    });

    const patients: PatientReportPatient[] = (data?.data ?? []).map((patient) => ({
        ...patient,
        reports: Array.isArray(patient.reports) ? patient.reports : [],
    }));

    const pagination = data?.pagination;

    const filteredPatients = useMemo(() => {
        const q = searchQuery.trim().toLowerCase();

        if (!q) return patients;

        return patients.filter((patient) => {
            const reports = Array.isArray(patient.reports) ? patient.reports : [];

            return (
                patient.name?.toLowerCase().includes(q) ||
                patient.id?.toLowerCase().includes(q) ||
                reports.some(
                    (report) =>
                        report.report_name?.toLowerCase().includes(q) ||
                        report.report_type?.toLowerCase().includes(q) ||
                        report.type_label?.toLowerCase().includes(q)
                )
            );
        });
    }, [patients, searchQuery]);

    // Reports shown in the modal: from the filter=all response, filtered by selected patient id
    const selectedPatientReports = useMemo(() => {
        if (!selectedPatient) return [];
        const allPatients: PatientReportPatient[] = modalData?.data ?? [];
        const match = allPatients.find((p) => p.id === selectedPatient.id);
        return Array.isArray(match?.reports) ? match.reports : [];
    }, [modalData, selectedPatient]);

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

    const openReportFile = (report: PatientReportItem) => {
        const fileUrl = report.files?.url;
        if (fileUrl) {
            window.open(fileUrl, "_blank", "noopener,noreferrer");
        }
    };

    const downloadAllReports = (patient: PatientReportPatient) => {
        const reports = Array.isArray(patient.reports) ? patient.reports : [];

        reports.forEach((report) => {
            if (report.files?.url) {
                window.open(report.files.url, "_blank", "noopener,noreferrer");
            }
        });
    };

    return (
        <div className="space-y-4 sm:space-y-6 md:px-4">
            {/* Back Button */}
            <Button
                onClick={() => router.back()}
                className="gap-2 h-9 sm:h-10 text-sm"
                size="sm"
            >
                <ArrowLeft className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                Back
            </Button>

            {/* Header Section */}
            <div className="flex items-start sm:items-center justify-between gap-3 flex-wrap sm:flex-nowrap">
                <div className="flex-1 min-w-0">
                    <h1 className="text-base xs:text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold text-primary tracking-tight truncate">
                        Patient Reports
                    </h1>
                    <p className="text-[10px] xs:text-xs sm:text-sm text-muted-foreground mt-0.5 sm:mt-1 truncate">
                        View and manage patient medical reports
                    </p>
                </div>

                <Button
                    variant="outline"
                    className="gap-1.5 sm:gap-2 w-auto shrink-0 h-8 xs:h-9 sm:h-10 text-[11px] xs:text-xs sm:text-sm"
                    disabled
                >
                    <Download className="h-3 w-3 xs:h-3.5 sm:h-4 xs:w-3.5 sm:w-4" />
                    <span className="xs:hidden">Export All</span>
                </Button>
            </div>

            {/* Search Bar */}
            <div className="relative w-full">
                <Search className="absolute left-3 top-1/2 h-3.5 w-3.5 sm:h-4 sm:w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    placeholder="Search by patient name, report name, or report type..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="pl-8 sm:pl-9 h-9 sm:h-10 text-xs sm:text-sm"
                />
            </div>

            {/* Loading State */}
            {isLoading && (
                <Card className="border-border">
                    <CardContent className="py-8 sm:py-12 text-center text-muted-foreground text-sm sm:text-base">
                        <div className="animate-pulse">Loading patient reports...</div>
                    </CardContent>
                </Card>
            )}

            {/* Error State */}
            {isError && (
                <Card className="border-border">
                    <CardContent className="py-8 sm:py-12 text-center text-red-500 text-sm sm:text-base">
                        Failed to load patient reports.
                        <div className="mt-2 text-xs sm:text-sm">
                            {(error as Error)?.message || "Something went wrong"}
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Main Content */}
            {!isLoading && !isError && (
                <>
                    {/* Patient Cards Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 md:gap-6">
                        {filteredPatients.map((patient) => (
                            <PatientReportCard
                                key={patient.id}
                                patient={patient}
                                onViewReports={setSelectedPatient}
                            />
                        ))}
                    </div>

                    {/* No Results */}
                    {filteredPatients.length === 0 && (
                        <Card className="border-border">
                            <CardContent className="flex flex-col items-center justify-center py-8 sm:py-12">
                                <FileText className="h-10 w-10 sm:h-12 sm:w-12 text-muted-foreground/40 mb-2 sm:mb-3" />
                                <p className="font-medium text-sm sm:text-base mb-1">No patients found</p>
                                <p className="text-xs sm:text-sm text-muted-foreground">
                                    Try adjusting your search
                                </p>
                            </CardContent>
                        </Card>
                    )}

                    {/* Pagination */}
                    {pagination && pagination.last_page > 1 && searchQuery.trim() === "" && (
                        <PaginationControls
                            currentPage={pagination.current_page}
                            totalPages={pagination.last_page}
                            totalItems={pagination.total}
                            itemsPerPage={10}
                            onPageChange={setPage}
                        />
                    )}
                </>
            )}

            {/* Modal - Same as before */}
            {selectedPatient && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-2 sm:p-4 z-50">
                    <Card className="w-full max-w-3xl max-h-[90vh] sm:max-h-[80vh] overflow-y-auto mx-2 sm:mx-0">
                        <CardHeader className="sticky top-0 bg-background z-10 px-4 sm:px-6 py-3 sm:py-4">
                            <div className="flex justify-between items-start gap-3 sm:gap-4">
                                <div className="min-w-0 flex-1">
                                    <CardTitle className="text-lg sm:text-xl md:text-2xl truncate">
                                        {selectedPatient.name}
                                    </CardTitle>
                                    <CardDescription className="text-xs sm:text-sm truncate">
                                        {selectedPatient.patient_id}
                                    </CardDescription>
                                </div>

                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => setSelectedPatient(null)}
                                    className="h-8 w-8 sm:h-9 sm:w-9 shrink-0"
                                >
                                    <X className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent className="px-4 sm:px-6 py-4 sm:py-5">
                            <h3 className="font-semibold text-sm sm:text-base mb-3 sm:mb-4">
                                Medical Reports
                            </h3>

                            <div className="space-y-2 sm:space-y-3">
                                {modalLoading ? (
                                    <div className="text-center text-muted-foreground py-8 sm:py-12 text-sm">
                                        Loading reports...
                                    </div>
                                ) : selectedPatientReports.length > 0 ? (
                                    selectedPatientReports.map((report) => (
                                        <Card key={report.id} className="border-border">
                                            <CardContent className="p-3 sm:p-4">
                                                <div className="flex flex-col sm:flex-row items-start justify-between gap-3 sm:gap-4">
                                                    <div className="flex gap-2 sm:gap-3 min-w-0 w-full sm:w-auto">
                                                        <div className="h-8 w-8 sm:h-10 sm:w-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                                                            {getReportTypeIcon(report.report_type)}
                                                        </div>

                                                        <div className="min-w-0 flex-1">
                                                            <p className="font-medium text-sm sm:text-base truncate">
                                                                {report.report_name}
                                                            </p>
                                                            <p className="text-xs sm:text-sm text-muted-foreground">
                                                                {report.type_label}
                                                            </p>

                                                            <div className="flex flex-wrap items-center gap-1 sm:gap-2 mt-1 text-[10px] sm:text-xs text-muted-foreground">
                                                                <Calendar className="h-2.5 w-2.5 sm:h-3 sm:w-3" />
                                                                <span>
                                                                    Uploaded{" "}
                                                                    {report.uploaded_at ||
                                                                        report.report_date_formatted}
                                                                </span>
                                                                <span className="hidden xs:inline">•</span>
                                                                <span className="capitalize">
                                                                    {report.status}
                                                                </span>
                                                            </div>

                                                            {report.files?.name && (
                                                                <p className="text-[9px] sm:text-xs text-muted-foreground mt-1 truncate">
                                                                    File: {report.files.name}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>

                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="gap-1.5 sm:gap-2 h-8 sm:h-9 text-xs sm:text-sm w-full sm:w-auto"
                                                        onClick={() => openReportFile(report)}
                                                        disabled={!report.files?.url}
                                                    >
                                                        <Eye className="h-3 w-3 sm:h-3.5 sm:w-3.5" />
                                                        View
                                                    </Button>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))
                                ) : (
                                    <div className="text-center text-muted-foreground py-8 sm:py-12 text-sm">
                                        No reports available
                                    </div>
                                )}
                            </div>
                        </CardContent>

                        <CardFooter className="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 px-4 sm:px-6 py-3 sm:py-4 sticky bottom-0 bg-background">
                            <Button
                                variant="outline"
                                onClick={() => setSelectedPatient(null)}
                                className="w-full sm:w-auto h-9 sm:h-10 text-sm"
                            >
                                Close
                            </Button>

                            <Button
                                className="gap-2 w-full sm:w-auto h-9 sm:h-10 text-sm"
                                onClick={() => downloadAllReports(selectedPatient)}
                                disabled={!selectedPatientReports.length}
                            >
                                <Download className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                Download All
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            )}
        </div>
    );
}