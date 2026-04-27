import { Button } from '@/components/ui/button';
import type { DoctorAvailabilitySlot } from '@/types/doctor-details';

interface TimeSelectorProps {
  slots: DoctorAvailabilitySlot[];
  selectedSlot: DoctorAvailabilitySlot | null;
  onSelectSlot: (slot: DoctorAvailabilitySlot) => void;
}

const TimeSelector = ({ slots, selectedSlot, onSelectSlot }: TimeSelectorProps) => {

  console.log("slots" ,slots);
  

  // console.log("time slots", slots);

  const formatTime = (time: string) => {
    if (!time) return "";

    // Case 1: already 24-hour format (13:00:00)
    if (time.includes(":") && time.length >= 5 && !time.includes("AM") && !time.includes("PM")) {
      return time.slice(0, 5);
    }

    // Case 2: 12-hour format (01:00 PM)
    const parsed = new Date(`1970-01-01 ${time}`);
    if (!isNaN(parsed.getTime())) {
      return parsed.toLocaleTimeString("en-GB", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      });
    }

    // Fallback
    return time;
  };

  const isSlotAvailable = (slot: DoctorAvailabilitySlot) => {
    return slot.available && slot.booked_count < slot.capacity;
  };

  return (
    <div className="bg-surface-container-low/50 rounded-2xl space-y-4 p-4">
      {/* Mobile: Horizontal scroll */}
      <div className="block md:hidden">
        <div className="overflow-x-auto no-scrollbar">
          <div className="flex gap-3 min-w-max">
            {slots.map((timeSlot) => {
              const available = isSlotAvailable(timeSlot);
              const isSelected = selectedSlot?.id === timeSlot.id;

              return (
                <Button
                  key={timeSlot.id}
                  variant={isSelected ? "default" : "outline"}
                  onClick={() => available && onSelectSlot(timeSlot)}
                  disabled={!available}
                  className={`flex-shrink-0 py-3 px-4 rounded-lg text-xs font-bold h-auto min-w-27.5 ${isSelected
                      ? 'bg-primary text-white hover:bg-primary'
                      : available
                        ? 'bg-white text-on-surface-variant hover:bg-surface-container'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    }`}
                >
                  <div className="flex flex-col items-center">
                    <span>{formatTime(timeSlot.start_time)} - {formatTime(timeSlot.end_time)}</span>
                    <span className="text-[9px] mt-1 opacity-70">{timeSlot.consultation_type_label}</span>
                    {!available && (
                      <span className="text-[8px] mt-1 text-red-500">Booked</span>
                    )}
                  </div>
                </Button>
              );
            })}
          </div>
        </div>
      </div>

      {/* Desktop: Grid of 3 or 4 columns */}
      <div className="hidden md:grid md:grid-cols-3 gap-3">
        {slots.map((timeSlot) => {
          const available = isSlotAvailable(timeSlot);
          const isSelected = selectedSlot?.id === timeSlot.id;

          return (
            <Button
              key={timeSlot.id}
              variant={isSelected ? "default" : "outline"}
              onClick={() => available && onSelectSlot(timeSlot)}
              disabled={!available}
              className={`py-3 px-4 rounded-lg text-xs font-bold h-auto w-full ${isSelected
                  ? 'bg-primary text-white hover:bg-primary'
                  : available
                    ? 'bg-white text-on-surface-variant hover:bg-surface-container'
                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                }`}
            >
              <div className="flex flex-col items-center">
                <span>{formatTime(timeSlot.start_time)} - {formatTime(timeSlot.end_time)}</span>
                <span className="text-[9px] mt-1 opacity-70">{timeSlot.consultation_type_label}</span>
                {!available && (
                  <span className="text-[8px] mt-1 text-red-500">Booked</span>
                )}
              </div>
            </Button>
          );
        })}
      </div>
    </div>
  );
};

export default TimeSelector;

// import { Clock } from 'lucide-react';
// import type { DoctorAvailabilitySlot } from '@/types/doctor-details';

// interface TimeSelectorProps {
//   slots: DoctorAvailabilitySlot[];
//   selectedSlot: DoctorAvailabilitySlot | null;
//   onSelectSlot: (slot: DoctorAvailabilitySlot) => void;
// }

// const TimeSelector = ({ slots, selectedSlot, onSelectSlot }: TimeSelectorProps) => {

//   const formatTime = (time: string) => {
//     return time.substring(0, 5);
//   };

//   const isSlotAvailable = (slot: DoctorAvailabilitySlot) => {
//     return slot.available && slot.booked_count < slot.capacity;
//   };

//   return (
//     <div className="space-y-3">
//       <label className="text-sm font-bold text-primary flex items-center gap-2">
//         <Clock className="w-4 h-4" />
//         Select Time Slot
//       </label>

//       {/* Only this part changed - Horizontal scroll */}
//       <div className="overflow-x-auto scrollbar-hide">
//         <div className="flex gap-3 min-w-max">
//           {slots.map((slot) => {
//             const available = isSlotAvailable(slot);
//             const isSelected = selectedSlot?.id === slot.id;

//             return (
//               <button
//                 key={slot.id}
//                 onClick={() => available && onSelectSlot(slot)}
//                 disabled={!available}
//                 className={`p-3 rounded-xl text-center transition-all min-w-[120px] ${isSelected
//                     ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20'
//                     : available
//                       ? 'bg-surface-container-low text-primary hover:bg-emerald-50 border border-outline-variant/10'
//                       : 'bg-gray-100 text-gray-400 cursor-not-allowed'
//                   }`}
//               >
//                 <div className="flex items-center justify-center gap-1 mb-1">
//                   <Clock className="w-3 h-3" />
//                   <span className="text-xs font-bold">
//                     {formatTime(slot.start_time)} - {formatTime(slot.end_time)}
//                   </span>
//                 </div>
//                 <div className="text-[10px] font-normal opacity-80">
//                   {slot.consultation_type_label}
//                 </div>
//                 {!available && (
//                   <div className="text-[8px] mt-1 text-red-500 font-medium">
//                     Booked
//                   </div>
//                 )}
//               </button>
//             );
//           })}
//         </div>
//       </div>
//     </div>
//   );
// };

// export default TimeSelector;