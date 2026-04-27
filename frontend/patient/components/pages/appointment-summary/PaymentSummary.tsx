interface PaymentSummaryProps {
  fee: number;
  serviceFee: number;
  discount: number;
}

const PaymentSummary = ({ fee, serviceFee, discount }: PaymentSummaryProps) => {
  const total = fee + serviceFee - discount;

  return (
    <div className="p-8 pt-10 space-y-6">
      <div className="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-4">
        Payment Summary
      </div>
      
      <div className="space-y-3">
        <div className="flex justify-between items-center text-sm">
          <span className="text-on-surface-variant font-medium">
            Consultation Fee
          </span>
          <span className="font-bold text-primary">
            ₹{fee}.00
          </span>
        </div>
        
        <div className="flex justify-between items-center text-sm">
          <span className="text-on-surface-variant font-medium">
            Service Fee
          </span>
          <span className="font-bold text-primary">
            ₹{serviceFee}.00
          </span>
        </div>
        
        {discount > 0 && (
          <div className="flex justify-between items-center text-sm text-emerald-600">
            <span className="font-medium">
              Discount
            </span>
            <span className="font-bold">
              -₹{discount}.00
            </span>
          </div>
        )}
        
        <div className="pt-4 border-t border-outline-variant/10">
          <p className="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">
            Total Amount
          </p>
          <p className="text-3xl font-bold text-primary font-headline tracking-tight">
            ₹{total}
          </p>
          <p className="text-xs text-on-surface-variant mt-1">
            Inclusive of all taxes
          </p>
        </div>
      </div>
    </div>
  );
};

export default PaymentSummary;