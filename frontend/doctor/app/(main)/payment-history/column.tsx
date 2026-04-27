"use client";

import type { ColumnDef } from "@tanstack/react-table";
import type { TransactionItem } from "@/types/transactions";
import { Badge } from "@/components/ui/badge";
import { getStatusColor } from "@/src/utils/getStatusColor";



export const transactionColumns: ColumnDef<TransactionItem>[] = [
  {
    accessorKey: "patient_name",
    header: "Patient Name",
    cell: ({ row }) => (
      <span className="font-medium">{row.original.patient_name || "-"}</span>
    ),
  },
  {
    accessorKey: "doctor_name",
    header: "Doctor Name",
    cell: ({ row }) => row.original.doctor_name || "-",
  },
  {
    accessorKey: "amount",
    header: "Amount",
    cell: ({ row }) => {
      const { amount, currency } = row.original;
      return `${currency || "INR"} ${amount || "0"}`;
    },
  },
  {
    accessorKey: "status_label",
    header: "Status",
    cell: ({ row }) => {
      const item = row.original;
      return (
        // <Badge variant={getStatusVariant(item.status) as any}>
        //   {item.status_label || "-"}
        // </Badge>
        <Badge
          className={`${getStatusColor(
            "payment", 
            item.status
          )} gap-1`}
        >
          {item.status_label || "Completed"}
        </Badge>
      );
    },
  },
  {
    accessorKey: "payment_type",
    header: "Payment Type",
    cell: ({ row }) => row.original.payment_type || "-",
  },
  {
    accessorKey: "payment_method",
    header: "Payment Method",
    cell: ({ row }) => row.original.payment_method || "-",
  },
  {
    accessorKey: "transaction_id",
    header: "Transaction ID",
    cell: ({ row }) => row.original.transaction_id || "-",
  },
  {
    accessorKey: "order_id",
    header: "Order ID",
    cell: ({ row }) => row.original.order_id || "-",
  },
  {
    accessorKey: "date",
    header: "Date",
    cell: ({ row }) => row.original.date || "-",
  },
];