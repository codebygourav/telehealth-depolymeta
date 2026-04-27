import { Pill, Info, Clock, CheckCircle2 } from 'lucide-react';
import { MedicineDetail } from '@/types/prescriptions';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';

interface MedicineItemProps {
  medicine: MedicineDetail;
}

export const MedicineItem = ({ medicine }: MedicineItemProps) => {
  const isPast = medicine.status?.toLowerCase() === 'past';
  console.table(medicine);
  return (
    <div className="p-6 bg-surface-container-low rounded-[1.5rem] border border-outline-variant/5 space-y-4 group transition-all">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="flex items-start gap-4">
          <div>
            <div className="flex items-center gap-2 flex-wrap">
              <h4 className="text-xl font-bold text-primary">{medicine.name}</h4>
              <Badge variant="outline" className={cn(
                "rounded-full uppercase font-semibold py-0 h-5 border-none",
                isPast ? "bg-surface-container text-red" : "bg-emerald-100 text-emerald-700"
              )}>
                {medicine.status || "Active"}
              </Badge>
            </div>
            <p className="text-on-surface-variant font-medium text-sm mt-1">
              {medicine.dosage} • {medicine.frequencylabel || medicine.frequency} • {medicine.meal && (
                <span className="capitalize  text-sm" > {medicine.meal.replace('_', ' ')}</span>
              )}
            </p>
            <div className="flex items-center gap-2 mt-1">
              <span className="text-xs font-semibold  tracking-widest text-on-surface-variant">Period:</span>
              <span className="text-xs text-on-surface-variant  font-semibold">
                {medicine.start_date} - {medicine.end_date}
              </span>
            </div>
          </div>
        </div>
        <div className="flex sm:block items-center justify-between sm:text-right border-t sm:border-t-0 pt-3 sm:pt-0 border-outline-variant/5">
          <div className="flex items-center sm:justify-end gap-1.5 text-emerald-600 font-bold">
            <Clock className="w-3.5 h-3.5" />
            <p className="text-sm uppercase tracking-widest">
              {medicine.times}
            </p>
          </div>
          <p className="text-sm font-medium text-on-surface-variant/60 mt-0.5 sm:mt-1">
            {medicine.type}
          </p>
        </div>
      </div>

      <div className="pt-4 border-t border-outline-variant/10 space-y-3">
        {medicine.instructions && medicine.instructions.length > 0 && (
          <div className="flex items-start gap-2 text-on-surface-variant">
            <Info className="w-4 h-4 mt-1 flex-shrink-0 text-primary/60" />
            <div className="text-sm font-medium leading-relaxed">
              <span className="font-bold text-primary mr-1">Instruction:</span>
              {medicine.instructions.join(', ')}
            </div>
          </div>
        )}

      </div>
    </div>
  );
};

