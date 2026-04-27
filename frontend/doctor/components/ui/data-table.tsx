"use client";

import * as React from "react";
import {
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  ColumnDef,
  flexRender,
} from "@tanstack/react-table";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Loader2, X, Search } from "lucide-react";
import PaginationControls from "@/components/pagination/PaginationControls";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import MobileCardView from "../custom/MobileCardView";

export interface FilterOption {
  label: string;
  value: string;
}

export interface DataTableFilter {
  column: string;
  label: string;
  options: FilterOption[];
  value?: string;
  onChange: (value: string) => void;
}

interface DataTableProps<T> {
  columns: ColumnDef<T>[];
  data: T[];

  loading?: boolean;

  pageCount?: number;
  currentPage?: number;
  totalItems?: number;
  itemsPerPage?: number;
  onPageChange?: (page: number) => void;

  enableSearch?: boolean;
  searchValue?: string;
  onSearch?: (value: string) => void;

  filters?: DataTableFilter[];
  onClearFilters?: () => void;
}

export function DataTable<T>({
  columns,
  data,
  loading = false,
  pageCount = 1,
  currentPage = 1,
  totalItems = 0,
  itemsPerPage = 10,
  onPageChange,
  enableSearch = true,
  searchValue = "",
  onSearch,
  filters = [],
  onClearFilters,
}: DataTableProps<T>) {
  const table = useReactTable({
    data,
    columns,
    pageCount,
    manualPagination: true,
    manualFiltering: false, // Change to false to enable client-side filtering
    manualSorting: true,
    state: {
      globalFilter: searchValue, // Add this
    },
    onGlobalFilterChange: (value) => {
      onSearch?.(value as string); // Trigger search when filter changes
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
  });

  // Clear all filters
  const handleClearAll = () => {
    if (onClearFilters) {
      onClearFilters();
    }
    if (onSearch) {
      onSearch("");
    }
    table.setGlobalFilter("");
  };

  return (
    <div className="space-y-4">
      {/* Toolbar: Search + Filters */}
      <div className="flex flex-wrap items-center gap-3">
        {enableSearch && (
          <div className="relative flex-1 min-w-50">
            <Search className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
            <Input
              placeholder="Search..."
              value={searchValue}
              onChange={(e) => onSearch?.(e.target.value)}
              className="h-9 pl-8 text-sm"
            />
          </div>
        )}

        <div className="flex flex-wrap items-center gap-2">
          {filters.map((filter) => (
            <Select
              key={filter.column}
              value={filter.value || "all"}
              onValueChange={filter.onChange}
            >
              <SelectTrigger className="h-9 w-fit min-w-32.5 text-sm">
                <SelectValue placeholder={filter.label} />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All {filter.label}</SelectItem>
                {filter.options.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          ))}

          {(searchValue || filters.some(f => f.value && f.value !== "all")) && (
            <Button
              variant="ghost"
              onClick={handleClearAll}
              className="h-9 px-3 text-muted-foreground text-sm"
            >
              Reset
              <X className="ml-2 h-3.5 w-3.5" />
            </Button>
          )}
        </div>
      </div>

      <MobileCardView
        table={table}
        columns={columns}
        loading={loading}
        statusType="appointment"
      />

      {/* Table */}
      <div className="hidden md:block border rounded-lg overflow-hidden bg-white [&_td]:border-b [&_th]:border-b">
        <Table className="w-full text-sm">
          <TableHeader className="bg-muted/50">
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => (
                  <TableHead
                    key={header.id}
                    className="px-3 py-3 sm:px-4 text-xs sm:text-sm font-semibold"
                  >
                    {header.isPlaceholder
                      ? null
                      : flexRender(
                        header.column.columnDef.header,
                        header.getContext()
                      )}
                  </TableHead>
                ))}
              </TableRow>
            ))}
          </TableHeader>

          <TableBody>
            {loading ? (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="text-center py-10"
                >
                  <Loader2 className="animate-spin mx-auto h-5 w-5" />
                </TableCell>
              </TableRow>
            ) : table.getRowModel().rows.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow key={row.id}>
                  {row.getVisibleCells().map((cell) => (
                    <TableCell
                      key={cell.id}
                      className="px-3 py-3 sm:px-4 align-middle text-xs sm:text-sm"
                    >
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="text-center py-10 text-sm text-muted-foreground"
                >
                  No data found
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      {onPageChange && pageCount > 1 && (
        <PaginationControls
          currentPage={currentPage}
          totalPages={pageCount}
          totalItems={totalItems}
          itemsPerPage={itemsPerPage}
          onPageChange={onPageChange}
        />
      )}
    </div>
  );
}