import { useAuth } from '@/context/userContext';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { Download, FileDownIcon } from 'lucide-react';
import React from 'react';

interface TransactionData {
    transaction_id?: string;
    id?: string;
    status?: string;
    status_label?: string;
    date?: string;
    amount?: string;
    currency?: string;
    paid_to?: string;
    order_id?: string;
    payment_method?: string;
    bank_name?: string;
    upi_id?: string;
    account_details?: string;
    patient_name?: string;
    doctor_name?: string;
}

interface TransactionReceiptPDFProps {
    transaction: TransactionData;
}

const formatAmountString = (amount: string, currency: string) => {
    const formattedNum = new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(amount));
    // jsPDF default font does not support the ₹ symbol
    return `Rs. ${formattedNum}`;
};

function formatDateForPDF(dateStr?: string) {
    if (!dateStr) return 'N/A';
    try {
        const dt = new Date(dateStr);
        const dateOptions: Intl.DateTimeFormatOptions = {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        };
        const timeOptions: Intl.DateTimeFormatOptions = {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        };
        const datePart = dt.toLocaleDateString('en-GB', dateOptions);
        const timePart = dt
            .toLocaleTimeString('en-US', timeOptions)
            .replace(/:\d\d /, ' ');
        return `${datePart}, ${timePart}`;
    } catch {
        return dateStr;
    }
}

export const TransactionReceiptPDF: React.FC<TransactionReceiptPDFProps> = ({
    transaction,
}) => {
    const { user } = useAuth();

    const generatePDF = () => {
        if (!transaction) return;

        try {
            const doc = new jsPDF();

            // Title
            doc.setFontSize(20);
            doc.setTextColor(22, 163, 74); // Green
            doc.text('Transaction Receipt', 14, 20);

            // Draw header info (one field per line)
            doc.setFontSize(12);
            doc.setTextColor(100, 100, 100);

            // Basically "row" y position
            let y = 30;
            const lineHeight = 7;

            // Each header row at left, value at right (trying to match visual of image)
            const leftX = 14;
            const valueX = 60;
            const entries = [
                [
                    'Status:',
                    transaction.status_label || transaction.status || 'N/A',
                ],
                ['Date:', formatDateForPDF(transaction.date)],
                [
                    'Name:',
                    `${user?.first_name || ''} ${user?.last_name || ''}`.trim() ||
                        'N/A',
                ],
                ['Email:', user?.email || 'N/A'],
                ['Phone:', user?.mobile_no || 'N/A'],
            ];
            entries.forEach(([label, value]) => {
                doc.text(label, leftX, y);
                doc.text(value, valueX, y);
                y += lineHeight;
            });

            // Divider
            y += 3;
            doc.setDrawColor(229, 231, 235);
            doc.line(leftX, y, 196, y);

            // Table Data
            let tableStartY = y + 8;
            const tableData: string[][] = [];

            tableData.push([
                'Amount',
                formatAmountString(
                    transaction.amount || '0',
                    transaction.currency || 'INR',
                ),
            ]);
            tableData.push([
                'Transaction ID',
                transaction.transaction_id || 'N/A',
            ]);
            tableData.push([
                'Paid To',
                transaction.paid_to ||
                    (transaction.doctor_name
                        ? `Dr. ${transaction.doctor_name}`
                        : 'N/A'),
            ]);

            if (transaction.order_id) {
                tableData.push(['Order ID', transaction.order_id]);
            }

            tableData.push([
                'Payment Method',
                transaction.payment_method
                    ? transaction.payment_method.charAt(0).toUpperCase() +
                      transaction.payment_method.slice(1)
                    : 'N/A',
            ]);

            if (
                transaction.payment_method?.toLowerCase() !== 'upi' &&
                transaction.bank_name
            ) {
                tableData.push(['Bank Name', transaction.bank_name]);
            } else if (
                transaction.payment_method?.toLowerCase() === 'upi' &&
                (transaction.upi_id || transaction.account_details)
            ) {
                tableData.push([
                    'UPI ID',
                    transaction.upi_id || transaction.account_details || 'N/A',
                ]);
            }

            // Add Table
            autoTable(doc, {
                startY: tableStartY,
                head: [],
                body: tableData,
                theme: 'plain',
                tableWidth: 'auto',
                styles: {
                    fontSize: 12,
                    cellPadding: { top: 3.5, right: 3, bottom: 3.5, left: 0 },
                    overflow: 'linebreak',
                },
                // Give space for label column and wider value, match the image
                columnStyles: {
                    0: {
                        fontStyle: 'bold',
                        textColor: [100, 100, 100],
                        cellWidth: 45,
                    },
                    1: { textColor: [0, 0, 0], cellWidth: 120 },
                },
                didParseCell(data) {
                    if (data.section === 'body' && data.column.index === 0) {
                        data.cell.styles.fontStyle = 'bold';
                    }
                },
            });

            // Footer
            const finalY =
                (doc as any).lastAutoTable?.finalY ||
                tableStartY + tableData.length * 8;
            doc.setDrawColor(229, 231, 235);
            doc.line(leftX, finalY + 10, 196, finalY + 10);

            doc.setFontSize(10);
            doc.setTextColor(153, 153, 153);
            doc.text(
                `Generated on ${new Date().toLocaleString('en-US')}`,
                105,
                finalY + 20,
                { align: 'center' },
            );

            // Save the file
            doc.save(
                `Receipt-${transaction.transaction_id || transaction.id || 'transaction'}.pdf`,
            );
        } catch (error) {
            console.error('Error generating PDF:', error);
        }
    };

    return (
        <div className="px-6 py-6 border-t border-gray-100 ">
            <h3 className="text-sm font-semibold text-gray-900 mb-4">
                Attachments
            </h3>

            <div className="flex items-center justify-between bg-surface-container-low rounded-2xl p-4">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center">
                        <FileDownIcon className="w-5 h-5 text-gray-700" />
                    </div>
                    <div>
                        <h2 className="font-semibold text-gray-900 text-sm">
                            Receipt.pdf
                        </h2>
                        <p className="text-xs text-gray-500 mt-0.5">
                            Online receipt for this transaction
                        </p>
                    </div>
                </div>
                <button
                    onClick={generatePDF}
                    className="text-gray-700 hover:text-gray-900 transition-colors p-2"
                >
                    <Download className="w-5 h-5" />
                </button>
            </div>
        </div>
    );
};
