"use client";

import * as React from "react";
import { DataTable } from "@/components/ui/data-table";
import { patientsColumns } from "./column";
import { usePatients } from "@/queries/usePatients";
import HeroSection from "@/components/ui/hero-section";
import { Button } from "@/components/ui";
import { ChevronLeft } from "lucide-react";
import { useRouter } from "next/navigation";

export default function PatientsPage() {

    const router = useRouter();

    const [page, setPage] = React.useState(1);
    const [searchInput, setSearchInput] = React.useState("");
    const [debouncedSearch, setDebouncedSearch] = React.useState("");
    const [status, setStatus] = React.useState("all");
    const [consultationType, setConsultationType] = React.useState("all");
    const per_page = 10;

    React.useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchInput);
            setPage(1);
        }, 500);

        return () => clearTimeout(timer);
    }, [searchInput]);

    const { data, isLoading, isFetching, error } = usePatients({
        page,
        per_page,
        search: debouncedSearch,
    });

    const patients = React.useMemo(() => {
        const allPatients = data?.data ?? [];
        return allPatients.filter((patient) => {
            const matchesStatus =
                status === "all" ||
                patient.status?.toLowerCase() === status.toLowerCase();
            const matchesType =
                consultationType === "all" ||
                patient.consultation_type?.toLowerCase() ===
                consultationType.toLowerCase();
            return matchesStatus && matchesType;
        });
    }, [data, status, consultationType]);

    const pageCount = data?.pagination?.last_page ?? 1;

    return (
        <div className="space-y-6 md:px-4 container-max-width w-full mx-auto">

            <HeroSection title="All Patients" description="Manage and view all patient appointments" />

            <div className="mt-4">
                {/* Back Button */}
                <Button
                    onClick={() => router.back()}
                    className="gap-2 h-9 sm:h-10 text-sm cursor-pointer"
                    size="sm"
                >
                    <ChevronLeft color="#fff" size={16} strokeWidth={4} />
                </Button>
            </div>

            {error ? (
                <div className="rounded-md border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                    {(error as any)?.response?.data?.message ||
                        (error as any)?.message ||
                        "Something went wrong"}
                </div>
            ) : null}

            <DataTable
                columns={patientsColumns}
                data={patients}
                loading={isLoading || isFetching}
                pageCount={data?.pagination?.last_page ?? 1}
                currentPage={page}
                totalItems={data?.pagination?.total ?? 0}
                itemsPerPage={per_page}
                onPageChange={setPage}
                enableSearch={true}
                searchValue={searchInput}
                onSearch={setSearchInput}
                filters={[
                    {
                        column: "status",
                        label: "Status",
                        value: status,
                        onChange: (val) => {
                            setStatus(val);
                            setPage(1);
                        },
                        options: [
                            { label: "Scheduled", value: "scheduled" },
                            { label: "Completed", value: "completed" },
                            { label: "Failed", value: "failed" },
                            { label: "Rescheduled", value: "rescheduled" },
                        ],
                    },
                    {
                        column: "consultation_type",
                        label: "Type",
                        value: consultationType,
                        onChange: (val) => {
                            setConsultationType(val);
                            setPage(1);
                        },
                        options: [
                            { label: "Video", value: "video" },
                            { label: "Clinic Visit", value: "in-person" },
                        ],
                    },
                ]}
                onClearFilters={() => {
                    setStatus("all");
                    setConsultationType("all");
                    setPage(1);
                }}
            />
        </div>
    );
}