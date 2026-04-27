import { ShieldCheck } from 'lucide-react';

const SummaryHeader = () => {
  return (
    <header className="flex items-center justify-between mb-12">
      <div className="flex items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold font-headline text-primary tracking-tight">
            Review Appointment
          </h1>
          <p className="text-on-surface-variant text-sm font-medium">
            Please confirm your session details
          </p>
        </div>
      </div>
      <div className="hidden md:flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold uppercase tracking-widest border border-emerald-100">
        <ShieldCheck className="w-4 h-4" />
        Secure Booking
      </div>
    </header>
  );
};

export default SummaryHeader;