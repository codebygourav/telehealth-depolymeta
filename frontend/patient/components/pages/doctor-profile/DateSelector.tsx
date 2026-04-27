import { ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { DoctorAvailabilitySlot } from '@/types/doctor-details';

interface DateSelectorProps {
  slots: DoctorAvailabilitySlot[];
  selectedSlot: DoctorAvailabilitySlot | null;
  onSelectSlot: (slot: DoctorAvailabilitySlot) => void;
}

const DateSelector = ({ slots, selectedSlot, onSelectSlot }: DateSelectorProps) => {
  const uniqueDates = Array.from(
    new Map(slots.map(slot => [slot.date, slot])).values()
  );

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return {
      day: date.getDate(),
      label: date.toLocaleDateString('en-US', { weekday: 'short' }),
      full: dateString,
    };
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-bold font-headline text-primary">Schedules</h3>
        <Button variant="ghost" size="sm" className="flex items-center gap-1 text-sm font-bold text-on-surface-variant">
          {new Date().toLocaleString('default', { month: 'long', year: 'numeric' })}
          <ChevronDown className="w-4 h-4" />
        </Button>
      </div>

      {/* Mobile: Horizontal scroll | Desktop: Grid of 5 */}
      <div className="block md:hidden">
        <div className="flex gap-3 overflow-x-auto no-scrollbar pb-2">
          {uniqueDates.map((slot) => {
            const { day, label, full } = formatDate(slot.date);
            const isSelected = selectedSlot?.date === full;

            return (
              <Button
                key={full}
                variant={isSelected ? "default" : "ghost"}
                onClick={() => onSelectSlot(slot)}
                className={`flex-shrink-0 w-14 h-auto py-4 rounded-xl flex flex-col items-center gap-1 ${isSelected
                    ? 'bg-primary text-white shadow-lg hover:bg-primary'
                    : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container'
                  }`}
              >
                <span className="text-lg font-bold">{day}</span>
                <span className="text-[10px] font-bold uppercase opacity-70">{label}</span>
              </Button>
            );
          })}
        </div>
      </div>

      {/* Desktop: Grid of 5 */}
      <div className="hidden md:grid md:grid-cols-5 gap-3">
        {uniqueDates.map((slot) => {
          const { day, label, full } = formatDate(slot.date);
          const isSelected = selectedSlot?.date === full;

          return (
            <Button
              key={full}
              variant={isSelected ? "default" : "ghost"}
              onClick={() => onSelectSlot(slot)}
              className={`w-full h-auto py-4 rounded-xl flex flex-col items-center gap-1 ${isSelected
                  ? 'bg-primary text-white shadow-lg hover:bg-primary'
                  : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container'
                }`}
            >
              <span className="text-lg font-bold">{day}</span>
              <span className="text-[10px] font-bold uppercase opacity-70">{label}</span>
            </Button>
          );
        })}
      </div>
    </div>
  );
};

export default DateSelector;


// import { Calendar } from 'lucide-react';

// interface DateSelectorProps {
//   dates: string[];
//   selectedDate: string | null;
//   onSelectDate: (date: string) => void;
// }

// const DateSelector = ({ dates, selectedDate, onSelectDate }: DateSelectorProps) => {

//   const formatDate = (dateStr: string) => {
//     const date = new Date(dateStr);
//     return {
//       day: date.toLocaleDateString('en-US', { weekday: 'short' }),
//       dateNum: date.getDate(),
//       month: date.toLocaleDateString('en-US', { month: 'short' })
//     };
//   };

//   return (
//     <div className="space-y-3">
//       <label className="text-sm font-bold text-primary flex items-center gap-2">
//         <Calendar className="w-4 h-4" />
//         Select Date
//       </label>
//       <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2">
//         {dates.map((date) => {
//           const { day, dateNum, month } = formatDate(date);
//           const isSelected = selectedDate === date;

//           return (
//             <button
//               key={date}
//               onClick={() => onSelectDate(date)}
//               className={`p-3 rounded-xl text-center transition-all ${isSelected
//                   ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20'
//                   : 'bg-surface-container-low text-primary hover:bg-emerald-50 border border-outline-variant/10'
//                 }`}
//             >
//               <div className="text-xs font-bold">{day}</div>
//               <div className="text-lg font-bold">{dateNum}</div>
//               <div className="text-[10px] opacity-80">{month}</div>
//             </button>
//           );
//         })}
//       </div>
//     </div>
//   );
// };

// export default DateSelector;