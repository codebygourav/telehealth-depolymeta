import { flexRender, Table, ColumnDef } from "@tanstack/react-table";
import { Loader2, CreditCard, User, Calendar, DollarSign, Package, Hash } from "lucide-react";
import { getStatusColor, StatusType } from "@/src/utils/getStatusColor";

interface MobileCardViewProps<TData> {
    table: Table<TData>;
    columns: ColumnDef<TData, any>[];
    loading: boolean;
    statusType?: StatusType;
}

const MobileCardView = <TData,>({ table, columns, loading, statusType = "appointment", }: MobileCardViewProps<TData>) => {
    const rows = table.getRowModel().rows;

    // Helper function to get icon based on header text
    const getIcon = (headerText: string | undefined ) => {
        const text = headerText?.toLowerCase() || "";
        if (text.includes("name") || text.includes("customer")) return <User className="h-3.5 w-3.5" />;
        if (text.includes("date")) return <Calendar className="h-3.5 w-3.5" />;
        if (text.includes("amount") || text.includes("total")) return <DollarSign className="h-3.5 w-3.5" />;
        if (text.includes("status")) return <CreditCard className="h-3.5 w-3.5" />;
        if (text.includes("items") || text.includes("product")) return <Package className="h-3.5 w-3.5" />;
        if (text.includes("id") || text.includes("invoice")) return <Hash className="h-3.5 w-3.5" />;
        return null;
    };

    if (loading) {
        return (
            <div className="flex flex-col items-center justify-center py-16 px-4">
                <Loader2 className="animate-spin h-8 w-8 text-primary mb-3" />
                <p className="text-sm text-muted-foreground">Loading data...</p>
            </div>
        );
    }

    if (!rows.length) {
        return (
            <div className="flex flex-col items-center justify-center py-16 px-4 text-center">
                <div className="bg-muted/20 rounded-full p-4 mb-4">
                    <Package className="h-8 w-8 text-muted-foreground" />
                </div>
                <p className="text-sm font-medium text-muted-foreground">No data found</p>
                <p className="text-xs text-muted-foreground/70 mt-1">Try adjusting your filters</p>
            </div>
        );
    }

    return (
        <div className="space-y-4 md:hidden pb-4">
            {rows.map((row, index) => {
                const cells = row.getVisibleCells();
                const firstCell = cells[0];
                const statusCell = cells.find(cell =>
                    cell.column.columnDef.header?.toString().toLowerCase().includes('status')
                );
                const statusValue = statusCell?.getValue() as string;


                return (
                    <div
                        key={row.id}
                        className="bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden"
                    >
                        {/* Top accent bar */}


                        {/* Card Header */}
                        <div className="p-4 pb-2">
                            <div className="flex items-center justify-between gap-3">
                                <div className="mt-2 font-semibold">
                                    {flexRender(firstCell?.column.columnDef.cell, firstCell?.getContext())}
                                </div>

                                {statusValue && (
                                    <span
                                        className={`text-xs font-medium px-2 py-0.5 rounded-full border ${getStatusColor(statusType || "appointment", statusValue)
                                            }`}
                                    >
                                        {statusValue}
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Divider */}
                        <div className="border-t border-gray-100 my-1" />

                        {/* Card Body - Status removed */}
                        <div className="p-4 pt-2 space-y-3">
                            {cells.slice(1).map((cell) => {
                                const headerText = typeof cell.column.columnDef.header === "string"
                                    ? cell.column.columnDef.header
                                    : undefined;

                                const isStatus = headerText?.toLowerCase().includes('status');

                                // Skip status column completely
                                if (isStatus) return null;

                                return (
                                    <div key={cell.id} className="flex items-center justify-between gap-3">
                                        <div className="flex items-center gap-2 text-gray-500">
                                            {getIcon(headerText)}
                                            <span className="text-sm">
                                                {headerText || cell.column.id}
                                            </span>
                                        </div>
                                        <div className="text-sm font-medium text-right wrap-break-word max-w-[60%]">
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                );
            })}
        </div>
    );
};

export default MobileCardView;