'use client';
import React from 'react';

import {
    ColumnDef,
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import PaginationControls from '@/components/ui/PaginationControls';
import { Button } from '@/components/ui/button';
import SelectField from '@/components/custom/SelectField';
import { Filter, Loader2, Search, X } from 'lucide-react';

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
    searchValue = '',
    onSearch,
    filters = [],
    onClearFilters,
}: DataTableProps<T>) {
    const table = useReactTable({
        data,
        columns,
        pageCount,
        manualPagination: true,
        manualFiltering: true,
        manualSorting: true,
        state: {},
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
    });

    const [showFilters, setShowFilters] = React.useState(false);

    return (
        <div className="space-y-4">
            {/* Toolbar: Search + Filters */}
            <div className="flex flex-col md:flex-row gap-4 items-stretch md:items-center">
                {enableSearch && (
                    <div className="relative flex-1">
                        <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            placeholder="Search records by name, doctor, or type..."
                            value={searchValue}
                            onChange={(e) => onSearch?.(e.target.value)}
                            className="w-full pl-12 pr-4 py-4 bg-white border border-gray-100 rounded-2xl shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-gray-100 transition-all"
                        />
                    </div>
                )}

                {filters.length > 0 && (
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className={`flex items-center gap-2 px-8 py-4 bg-white border rounded-2xl shadow-sm font-bold text-gray-700 hover:bg-gray-50 transition-all ${showFilters ? 'border-gray-300 bg-gray-50' : 'border-gray-100'
                            }`}
                    >
                        <Filter className="w-5 h-5" />
                        <span>Filters</span>
                    </button>
                )}
            </div>

            {/* Collapsible Filters section */}
            {showFilters && filters.length > 0 && (
                <div className="flex flex-wrap items-center gap-2 p-1 bg-gray-50/50 rounded-2xl">

                    {/* {filters.map((filter) => (
                        <Select
                            key={filter.column}
                            value={filter.value || 'all'}
                            onValueChange={filter.onChange}
                        >
                            <SelectTrigger className="h-[50px] w-fit min-w-[150px] rounded-xl border-gray-100 shadow-sm bg-white">
                                <SelectValue placeholder={filter.label} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All {filter.label}
                                </SelectItem>
                                {filter.options.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    ))} */}
                
                    {filters.map((filter) => (
                        <SelectField
                            key={filter.column}
                            name={filter.column}
                            value={filter.value || 'all'}
                            onChange={filter.onChange}
                            placeholder={filter.label}
                            options={[
                                { value: 'all', label: `All ${filter.label}` },
                                ...filter.options,
                            ]}
                            triggerClassName="h-[50px] w-fit min-w-[150px] rounded-xl border-gray-100 shadow-sm bg-white"
                        />
                    ))}

                    {onClearFilters && (
                        <Button
                            variant="ghost"
                            onClick={onClearFilters}
                            className="h-[50px] px-4 rounded-xl text-gray-500 border border-gray-100 shadow-sm bg-white hover:bg-gray-50 flex items-center gap-2"
                        >
                            Reset
                            <X className="h-4 w-4" />
                        </Button>
                    )}
                </div>
            )}

            {/* Table */}
            <div className="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
                <div className="overflow-x-auto">
                    <Table className="w-full text-left">
                        <TableHeader>
                            <TableRow className="bg-gray-50/50 border-b border-gray-100 hover:bg-gray-50/50">
                                {table.getHeaderGroups().map((headerGroup) =>
                                    headerGroup.headers.map((header) => (
                                        <TableHead
                                            key={header.id}
                                            className="px-8 py-5 text-[11px] font-bold text-gray-500 uppercase tracking-[0.15em]"
                                        >
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                    header.column.columnDef.header,
                                                    header.getContext(),
                                                )}
                                        </TableHead>
                                    ))
                                )}
                            </TableRow>
                        </TableHeader>

                        <TableBody className="divide-y divide-gray-50">
                            {loading ? (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell
                                        colSpan={columns.length}
                                        className="text-center py-16"
                                    >
                                        <Loader2 className="animate-spin mx-auto h-6 w-6 text-gray-400" />
                                    </TableCell>
                                </TableRow>
                            ) : table.getRowModel().rows.length ? (
                                table.getRowModel().rows.map((row) => (
                                    <TableRow
                                        key={row.id}
                                        className="hover:bg-gray-50/30 transition-colors"
                                    >
                                        {row.getVisibleCells().map((cell) => (
                                            <TableCell
                                                key={cell.id}
                                                className="px-8 py-6 align-middle"
                                            >
                                                {flexRender(
                                                    cell.column.columnDef.cell,
                                                    cell.getContext(),
                                                )}
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell
                                        colSpan={columns.length}
                                        className="text-center py-16 text-gray-400 font-medium"
                                    >
                                        No data found
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            {/* Pagination */}
            {onPageChange && (
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
