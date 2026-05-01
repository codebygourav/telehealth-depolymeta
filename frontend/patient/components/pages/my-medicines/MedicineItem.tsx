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
    <div className="p-6 bg-light-gray global-radius-10 shadow-card-sm space-y-4 group transition-all">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div className="flex items-start gap-4">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h4 className="text-xl font-bold g-text-dark">{medicine.name}</h4>
              <Badge variant="outline" className={cn(
                "rounded-full uppercase font-semibold py-0 h-5 border-none",
                isPast ? "bg-surface-container text-red" : "bg-emerald-100 text-emerald-700"
              )}>
                {medicine.status || "Active"}
              </Badge>
            </div>
            <p className="mt-1 text-sm font-medium g-text-muted">
              {medicine.dosage} • {medicine.frequencylabel || medicine.frequency} • {medicine.meal && (
                <span className="text-sm capitalize" > {medicine.meal.replace('_', ' ')}</span>
              )}
            </p>
            <div className="flex items-center gap-2 mt-1">
              <span className="text-xs font-semibold tracking-widest g-text-muted ">Period:</span>
              <span className="text-xs font-semibold g-text-muted ">
                {medicine.start_date} - {medicine.end_date}
              </span>
            </div>
          </div>
        </div>
        <div className="flex items-center justify-between pt-3 sm:block sm:text-right sm:border-t-0 sm:pt-0 border-outline-variant/5">
          <div className="flex items-center sm:justify-end gap-1.5 g-text-dark font-bold">
            <Info className="w-3.5 h-3.5" />
            <p className="text-sm tracking-widest uppercase">
              {medicine.times}
            </p>
          </div>
          <p className="text-sm font-medium g-text-muted  mt-0.5 sm:mt-1">
            {medicine.type}
          </p>
        </div>
      </div>

      <div className="pt-3 space-y-2 border-t border-outline-variant/10">
        {medicine.instructions && medicine.instructions.length > 0 && (
          <div className="flex items-start gap-2 g-text-muted ">
            <Info className="flex-shrink-0 w-4 h-4 mt-1 text-success" />
            <div className="text-sm font-medium leading-relaxed">
              <span className="mr-1 font-bold g-text-dark">Instruction:</span>
              {medicine.instructions.join(', ')}
            </div>
          </div>
        )}

      </div>
    </div>
  );
};

