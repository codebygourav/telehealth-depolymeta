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
                <h3 className="text-[#1F1E1E] text-lg font-semibold">Schedules</h3>
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
            <div className="hidden md:grid md:grid-cols-4 gap-3">
                {uniqueDates.map((slot) => {
                    const { day, label, full } = formatDate(slot.date);
                    const isSelected = selectedSlot?.date === full;

                    return (
                        <Button
                            key={full}
                            variant={isSelected ? "default" : "ghost"}
                            onClick={() => onSelectSlot(slot)}
                            className={`w-full h-auto py-3 rounded-md flex flex-col items-center gap-1 ${isSelected
                                ? 'bg-primary text-white shadow-lg hover:bg-primary'
                                : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container'
                                }`}
                        >
                            <span className="text-base font-bold">{day}</span>
                            <span className="text-xs font-bold uppercase">{label}</span>
                        </Button>
                    );
                })}
            </div>
        </div>
    );
};

export default DateSelector;