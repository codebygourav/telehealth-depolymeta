"use client";

import type { ColumnDef } from "@tanstack/react-table";
import type { MedicineItem } from "@/types/medicines";
import { Badge } from "@/components/ui/badge";

export const medicineColumns: ColumnDef<MedicineItem>[] = [
  {
    accessorKey: "name",
    header: "Medicine Name",
    cell: ({ row }) => {
      return <span className="font-medium">{row.original.name}</span>;
    },
  },
  {
    accessorKey: "type",
    header: "Type",
    cell: ({ row }) => {
      return <Badge variant="outline">{row.original.type || "-"}</Badge>;
    },
  },
  {
    accessorKey: "category",
    header: "Category",
    cell: ({ row }) => row.original.category || "-",
  },
  {
    accessorKey: "created_at",
    header: "Created At",
    cell: ({ row }) => row.original.created_at || "-",
  },
  {
    accessorKey: "updated_at",
    header: "Updated At",
    cell: ({ row }) => row.original.updated_at || "-",
  },
];