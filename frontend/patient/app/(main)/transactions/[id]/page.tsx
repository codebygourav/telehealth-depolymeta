"use client";

import { useRouter } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { fetchTransactionById } from "@/api/transactions";
import {
    CreditCard,
    Smartphone,
    Banknote,
    CheckCircle,
    XCircle,
    Calendar,
    Clock,
    Hash,
    User,
    Stethoscope,
    Receipt,
    ArrowLeft,
    FileText,
    Link2,
    Download,
    File,
    Landmark,
    Building2,
    Copy,
    Check
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { TransactionReceiptPDF } from "@/components/pdf/TransactionReceiptPDF";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import { DetailHeader } from "@/components/custom/DetailHeader";

interface TransactionDetailProps {
    params: { id: string };
}

// Helper function to get status icon and color
const getStatusIcon = (status: string) => {
    switch (status?.toLowerCase()) {
        case "paid":
            return { icon: CheckCircle, color: "text-green-600", bg: "bg-green-50" };
        case "pending":
            return { icon: Clock, color: "text-yellow-600", bg: "bg-yellow-50" };
        case "cancelled":
        case "failed":
            return { icon: XCircle, color: "text-red-600", bg: "bg-red-50" };
        default:
            return { icon: Clock, color: "text-gray-600", bg: "bg-gray-50" };
    }
};

const getPaymentIcon = (method: string) => {
    switch (method?.toLowerCase()) {
        case "card":
            return CreditCard;
        case "upi":
            return Smartphone;
        default:
            return Banknote;
    }
};

const formatAmount = (amount: string, currency: string) => {
    return new Intl.NumberFormat("en-IN", {
        style: "currency",
        currency: currency || "INR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Number(amount));
};


export default function TransactionDetail({ params }: TransactionDetailProps) {
    const router = useRouter();
    const [transaction, setTransaction] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [copiedId, setCopiedId] = useState(false);


    useEffect(() => {
        const fetchTransaction = async () => {
            try {
                setLoading(true);
                const { id } = await params;
                const data = await fetchTransactionById(id, "");
                setTransaction(data);
            } catch (err: any) {
                console.error("Failed to fetch transaction:", err);
                if (err.response?.status === 401) {
                    setError("Unauthorized. Please login again.");
                    setTimeout(() => {
                        router.push("/login");
                    }, 2000);
                } else {
                    setError("Failed to load transaction details");
                }
            } finally {
                setLoading(false);
            }
        };

        fetchTransaction();
    }, [params, router]);

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        setCopiedId(true);
        setTimeout(() => setCopiedId(false), 2000);
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center min-h-screen">
                <p className="text-gray-500">Loading transaction details...</p>
            </div>
        );
    }

    if (error || !transaction) {
        return (
            <div className="max-w-2xl mx-auto p-6">
                <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                    <p className="text-red-600">{error || "Transaction not found"}</p>
                    <button
                        onClick={() => router.back()}
                        className="mt-4 text-sm text-blue-600 hover:text-blue-800 inline-flex items-center gap-1"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Go Back
                    </button>
                </div>
            </div>
        );
    }

    const StatusIcon = getStatusIcon(transaction.status).icon;
    const statusStyle = getStatusIcon(transaction.status);

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="max-w-2xl mx-auto">
                {/* Back Button and Download */}
                <DetailHeader
                    title="Transaction Details"
                    subtitle="Back to Transactions"
                />

                {/* Status Card */}
                <div className={`${statusStyle.bg} rounded-2xl p-6 mb-6 shadow-sm border border-gray-100`}>
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <StatusIcon className={`w-8 h-8 ${statusStyle.color}`} />
                            <div>
                                <p className={`font-semibold text-lg ${statusStyle.color}`}>
                                    {transaction.status_label || transaction.status}
                                </p>
                                <p className="font-medium text-xs text-gray-900">{transaction.date || "N/A"}</p>
                            </div>
                        </div>
                        <div className="text-right">
                            <p className="text-sm text-gray-600">Amount</p>
                            <p className="font-bold text-2xl text-gray-900">
                                {formatAmount(transaction.amount, transaction.currency)}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Main Clean Card */}
                <div className="overflow-hidden">
                    {/* Transaction ID */}
                    <div className="px-6 py-5 border-b border-gray-300 flex justify-between items-center">
                        <p className="text-sm text-gray-500">Transaction Id</p>
                        <div className="flex items-center gap-2">
                            <p className="font-mono font-semibold text-base text-gray-900">
                                {transaction.transaction_id && (
                                    <span>{transaction.transaction_id}</span>
                                )}
                            </p>
                            <button
                                onClick={() => copyToClipboard(transaction.id || transaction.transaction_id)}
                                className="text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                {copiedId ? (
                                    <Check className="w-4 h-4 text-green-500" />
                                ) : (
                                    <Copy className="w-4 h-4" />
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Details */}
                    <div className="divide-y divide-gray-200">
                        {/* Paid To */}
                        <div className="px-6 py-5 flex justify-between items-center">
                            <p className="text-gray-600">Paid To</p>
                            <div className="flex items-center gap-2">
                                <p className="font-medium text-gray-900">{transaction.paid_to || "N/A"}</p>
                            </div>
                        </div>

                        {/* Transaction ID (from API) */}
                        {transaction.transaction_id && (
                            <div className="px-6 py-5 flex justify-between items-center">
                                <p className="text-gray-600">Transaction ID</p>
                                <p className="font-mono text-gray-900">{transaction.transaction_id}</p>
                            </div>
                        )}

                        {/* Order ID */}
                        {transaction.order_id && (
                            <div className="px-6 py-5 flex justify-between items-center">
                                <p className="text-gray-600">Order ID</p>
                                <p className="font-mono text-gray-900 text-sm">{transaction.order_id}</p>
                            </div>
                        )}

                        {/* Payment Method */}
                        <div className="px-6 py-5 flex justify-between items-center">
                            <p className="text-gray-600">Payment method</p>
                            <p className="font-medium text-gray-900">{transaction.payment_method || "N/A"}</p>
                        </div>

                        {/* Show Bank/UPI details */}
                        {(transaction.payment_method?.toLowerCase() !== "upi" && transaction.bank_name) ||
                            (transaction.payment_method?.toLowerCase() === "upi" && (transaction.upi_id || transaction.account_details)) ? (
                            <div className="px-6 py-5 flex justify-between items-center">
                                <p className="text-gray-600">
                                    {transaction.payment_method?.toLowerCase() === "upi" ? "UPI ID" : "Bank Name"}
                                </p>
                                <div className="text-right">
                                    <p className="text-base text-gray-900">
                                        {transaction.payment_method?.toLowerCase() === "upi"
                                            ? (transaction.upi_id || transaction.account_details)
                                            : transaction.bank_name}
                                    </p>
                                </div>
                            </div>
                        ) : null}

                        {/* Patient Name */}
                        {transaction.patient_name && (
                            <div className="px-6 py-5 flex justify-between items-center">
                                <p className="text-gray-600">Patient Name</p>
                                <p className="font-medium text-gray-900">{transaction.patient_name}</p>
                            </div>
                        )}

                        {/* Doctor Name */}
                        {transaction.doctor_name && (
                            <div className="px-6 py-5 flex justify-between items-center">
                                <p className="text-gray-600">Doctor Name</p>
                                <p className="font-medium text-gray-900">Dr. {transaction.doctor_name}</p>
                            </div>
                        )}
                    </div>
                    {/* Attachments Section */}

                    <TransactionReceiptPDF transaction={transaction} />
                </div>
            </div>
        </div>
    );
}