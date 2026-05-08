"use client";

import * as React from "react";
import { DataTable } from "@/components/ui/data-table";
import { leaveColumns } from "@/app/(main)/my-leaves/column";
import { useLeave } from "@/queries/useLeave";
import HeroSection from "@/components/ui/hero-section";
import { Button } from "@/components/ui";
import { ChevronLeft } from "lucide-react";
import { useRouter } from "next/navigation";
import { ApplyLeaveModal } from "@/app/(main)/my-leaves/apply-leave-modal";

export default function LeavesPage() {

    const [isApplyModalOpen, setIsApplyModalOpen] = React.useState(false);
    const [page, setPage] = React.useState(1);
    const [searchInput, setSearchInput] = React.useState("");
    const [debouncedSearch, setDebouncedSearch] = React.useState("");
    const per_page = 5;

    React.useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchInput);
            setPage(1);
        }, 500);

        return () => clearTimeout(timer);
    }, [searchInput]);

    const { data, isLoading, isFetching, error } = useLeave({
        page,
        per_page,
        search: debouncedSearch,
    });

    const router = useRouter();
    const leaves = data?.data ?? [];
    const pageCount = data?.pagination?.last_page ?? 1;

    return (
        <div className="space-y-6 md:px-4 container-max-width w-full mx-auto">

            <HeroSection
                title="Medicine Inventory"
                description="Connect with world-class specialists curated for your health journey. Expert clinical care delivered with a human touch."
            />

            <div className="flex items-center justify-between">
                <Button
                    onClick={() => router.back()}
                    className="gap-2 h-9 sm:h-10 text-sm cursor-pointer"
                    size="sm"
                >
                    <ChevronLeft color="#fff" size={16} strokeWidth={4} />
                </Button>
                <Button
                    onClick={() => setIsApplyModalOpen(true)}
                    className="cursor-pointer py-2 h-auto px-4"
                >
                    Apply Leave
                </Button>
            </div>

            <ApplyLeaveModal 
                open={isApplyModalOpen} 
                onOpenChange={setIsApplyModalOpen} 
            />

            {error ? (
                <div className="rounded-md border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                    {(error as any)?.response?.data?.message ||
                        (error as any)?.message ||
                        "Something went wrong while fetching medicines."}
                </div>
            ) : null}

            <DataTable
                columns={leaveColumns}
                data={leaves}
                loading={isLoading || isFetching}
                pageCount={pageCount}
                currentPage={page}
                totalItems={data?.pagination?.total ?? 0}
                itemsPerPage={per_page}
                onPageChange={setPage}
                enableSearch={true}
                searchValue={searchInput}
                onSearch={setSearchInput}
            />
        </div>
    );
}