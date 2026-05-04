"use client";

import * as React from "react";

import {
    type ColumnDef,
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    type SortingState,
    useReactTable,
} from "@tanstack/react-table";
import { ArrowUpDown, Loader2, Search, X } from "lucide-react";

import { cn } from "@/lib/utils";
import {
    Button,
    Card,
    CardContent,
    Input,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

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
    type ColumnMeta = {
        headerClassName?: string;
        cellClassName?: string;
    };

    const [sorting, setSorting] = React.useState<SortingState>([]);
    const safePageCount = Math.max(1, pageCount);

    const table = useReactTable({
        data,
        columns,
        manualPagination: true,
        state: { sorting },
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });

    const showClear =
        !!onClearFilters &&
        (searchValue.trim().length > 0 || filters.some((f) => (f.value ?? "all") !== "all"));

    const entriesFrom = totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
    const entriesTo = totalItems === 0 ? 0 : Math.min(currentPage * itemsPerPage, totalItems);

    const pageNumbers = React.useMemo(() => {
        const maxButtons = 5;
        const safeCurrent = Math.min(Math.max(1, currentPage), safePageCount);
        const half = Math.floor(maxButtons / 2);

        let start = Math.max(1, safeCurrent - half);
        const end = Math.min(safePageCount, start + maxButtons - 1);
        start = Math.max(1, end - maxButtons + 1);

        return Array.from({ length: end - start + 1 }, (_, i) => start + i);
    }, [currentPage, safePageCount]);

    return (
        <>
            {/* Filter/Search header section */}
            <Card className="g-border-light container-max-width mx-auto w-full">
                <CardContent>
                    <div className="flex flex-col gap-3 global-radius sm:flex-row sm:items-center sm:justify-between">
                        {/* Filters/Search section for mobile: stacked vertically; desktop: horizontal */}
                        <div className="flex flex-col gap-2 w-full sm:flex-row sm:items-center sm:justify-between">
                            {enableSearch && (
                                <div className="relative w-full md:w-auto">
                                    <Search className="absolute -translate-y-1/2 pointer-events-none left-3 top-1/2 size-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search"
                                        value={searchValue}
                                        onChange={(e) => onSearch?.(e.target.value)}
                                        className="h-12 pl-9 global-radius"
                                    />
                                </div>
                            )}

                            {/* Filters row with proper spacing and full width on mobile */}
                            <div className="flex flex-row gap-2 w-full sm:w-auto sm:flex-row sm:items-center">
                                {filters.map((filter) => {
                                    // always default to 'all'
                                    const value = (filter.value ?? "all") === "all" ? "all" : (filter.value ?? "all");
                                    return (
                                        <Select
                                            key={filter.column}
                                            value={value}
                                            onValueChange={(val) => filter.onChange(val || "all")}
                                        >


                                            <SelectTrigger className="w-full sm:w-[170px] ">
                                                <SelectValue placeholder={filter.label} />
                                            </SelectTrigger>
                                            <SelectContent position="popper">
                                                <SelectItem value="all">All</SelectItem>
                                                {filter.options.map((opt) => (
                                                    <SelectItem key={`${filter.column}-${opt.value}`} value={opt.value}>
                                                        {opt.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    );
                                })}

                                {showClear && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={onClearFilters}
                                        className="justify-start sm:justify-center"
                                    >
                                        <X className="mr-2 size-4" />
                                        Clear
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Table section */}
            <div className="container-max-width mx-auto w-full overflow-x-auto g-border global-radius">
                <Table className="w-full table-auto">
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id} className="border-none bg-light-gray hover:bg-light-gray" >
                                {headerGroup.headers.map((header) => (
                                    (() => {
                                        const meta = header.column.columnDef.meta as unknown as ColumnMeta | undefined;
                                        return (
                                            <TableHead
                                                key={header.id}
                                                className={cn(
                                                    "h-12 px-3 sm:px-4 text-xs font-semibold g-text-dark border-none flex-wrap items-center",
                                                    meta?.headerClassName
                                                )}
                                            >
                                                {header.isPlaceholder ? null : header.column.getCanSort() ? (
                                                    <button
                                                        type="button"
                                                        onClick={header.column.getToggleSortingHandler()}
                                                        className="inline-flex items-center gap-1.5"
                                                    >
                                                        {flexRender(header.column.columnDef.header, header.getContext())}
                                                        <ArrowUpDown className="size-3.5 text-muted-foreground/70" />
                                                    </button>
                                                ) : (
                                                    flexRender(header.column.columnDef.header, header.getContext())
                                                )}
                                            </TableHead>
                                        );
                                    })()
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>

                    <TableBody>
                        {loading ? (
                            <TableRow className="hover:bg-transparent border-b">
                                <TableCell colSpan={columns.length} className="py-16 text-center">
                                    <Loader2 className="mx-auto size-6 animate-spin text-muted-foreground" />
                                </TableCell>
                            </TableRow>
                        ) : table.getRowModel().rows.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id} className="hover:bg-muted/20 border-b dsfdsgdf">
                                    {row.getVisibleCells().map((cell) => {
                                        const meta = cell.column.columnDef.meta as unknown as ColumnMeta | undefined;
                                        return (
                                            <TableCell
                                                key={cell.id}
                                                className={cn("px-3 sm:px-4 py-4 align-middle", meta?.cellClassName)}
                                            >
                                                {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                            </TableCell>
                                        );
                                    })}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow className="hover:bg-transparent">
                                <TableCell
                                    colSpan={columns.length}
                                    className="py-16 text-sm text-center text-muted-foreground"
                                >
                                    No data found
                                </TableCell>
                            </TableRow>
                        )}

                    </TableBody>
                </Table>
                {/* Pagination section */}
                {onPageChange ? (
                    <div className="flex flex-col w-full gap-3 px-5 py-5.5 m-0 border-light-gray-top  sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-xs text-muted-foreground">
                            Showing {entriesFrom} to {entriesTo} of {totalItems} entries
                        </p>

                        <div className="flex items-center gap-1.5">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => onPageChange(Math.max(1, currentPage - 1))}
                                disabled={currentPage <= 1}
                            >
                                Prev
                            </Button>

                            {pageNumbers.map((p) => (
                                <Button
                                    key={p}
                                    type="button"
                                    variant={p === currentPage ? "default" : "outline"}
                                    size="sm"
                                    onClick={() => onPageChange(p)}
                                    className={cn("h-8 w-8 px-0", p === currentPage && "hover:bg-primary/95")}
                                >
                                    {p}
                                </Button>
                            ))}

                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => onPageChange(Math.min(safePageCount, currentPage + 1))}
                                disabled={currentPage >= safePageCount}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                ) : null}
            </div>


        </>
    );
}
