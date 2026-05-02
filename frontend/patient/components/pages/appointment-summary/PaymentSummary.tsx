import { StatusBadge, StatusBadgeStatus } from "@/components/custom/StatusBadge";
import { Card, CardContent } from "@/components/ui";
import { AppointmentPayment } from "@/types/appointment-summary";
import { Calendar, CreditCard } from "lucide-react";

interface PaymentSummaryProps {
  payment: AppointmentPayment;
}

const PaymentSummary = ({ payment }: PaymentSummaryProps) => {

  return (
    <Card className="p-0 global-radius-10 g-border">
      <CardContent className="p-0">
        <div className="p-4 sm:p-5 md:p-6">
        <div className="flex items-center justify-between gap-2 mb-3">
          <div className="flex items-center gap-2">
          <CreditCard className="w-5 h-5 text-primary" />
            <span className="text-lg font-semibold text-on-surface">Payment Summary</span>
            </div>
            <StatusBadge status={payment.status as StatusBadgeStatus} label={payment.status_label || "N/A"} />
          </div>
      
      <div className="space-y-3">
        <div className="flex items-center justify-between text-sm">
          <span className="font-medium text-on-surface-variant">
            Consultation Fee
          </span>
          <span className="font-bold text-primary">
            {payment.consultation_fee_formatted}
          </span>
        </div>
        
        <div className="flex items-center justify-between text-sm">
          <span className="font-medium text-on-surface-variant">
            Service Fee
          </span>
          <span className="font-bold text-primary">
            {payment.admin_fee_formatted}
          </span>
        </div>
        
        {parseFloat(payment.discount_formatted) > 0 && (
          <div className="flex items-center justify-between text-sm text-emerald-600">
            <span className="font-medium">
              Discount
            </span>
            <span className="font-bold">
              -{payment.discount_formatted}
            </span>
          </div>
        )}
        
        <div className="flex items-center justify-between pt-4 border-t border-outline-variant/10">
          <p className="flex flex-col items-start gap-2 mb-1 span-12">
            Total Amount
            <span className="text-sm g-text-muted"> Inclusive of all taxes</span>
          </p>
          <p className="text-2xl font-bold tracking-tight text-primary font-headline">
            {payment.total_formatted}
          </p>
          </div>
        </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default PaymentSummary;