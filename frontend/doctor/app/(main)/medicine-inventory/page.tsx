"use client";

import * as React from "react";
import { DataTable } from "@/components/ui/data-table";
import { medicineColumns } from "@/app/(main)/medicine-inventory/column";
import { useMedicines } from "@/queries/useMedicines";

export default function MedicinesPage() {
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

  const { data, isLoading, isFetching, error } = useMedicines({
    page,
    per_page,
    search: debouncedSearch,
  });

  const medicines = data?.data ?? [];
  const pageCount = data?.pagination?.last_page ?? 1;

  return (
    <div className="space-y-6 md:px-4">
      <div>
        <h1 className="text-2xl font-semibold">Medicine Inventory</h1>
        <p className="text-sm text-muted-foreground">
          View and manage all medicines
        </p>
      </div>

      {error ? (
        <div className="rounded-md border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
          {(error as any)?.response?.data?.message ||
            (error as any)?.message ||
            "Something went wrong while fetching medicines."}
        </div>
      ) : null}

      <DataTable
        columns={medicineColumns}
        data={medicines}
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