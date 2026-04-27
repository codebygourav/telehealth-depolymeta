import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { ReactNode } from "react";
import { OPDSlotCard } from "./OPDSlotCard";
import { OPDSlot } from "@/types/schedule";

interface ScheduleCardProps {
    title: string;
    date: string;
    count: number;
    countLabel: string;
    emptyIcon?: ReactNode;
    emptyMessage?: string;
    emptySubMessage?: string;
    OPDSlotsForSelectedDate: OPDSlot[];
    selectedSlot?: OPDSlot;
    onSlotClick: (slot: OPDSlot) => void;
}

const DoctorOpdSchedule = ({
    title,
    date,
    count,
    countLabel,
    emptyIcon,
    OPDSlotsForSelectedDate,
    selectedSlot,
    onSlotClick,
    emptyMessage = "No data available",
    emptySubMessage = "Select a date to view details"
}: ScheduleCardProps) => {
    return (
        <Card className="border-border h-full py-0 flex flex-col">

            {/* Header */}
            <CardHeader className="bg-primary text-white rounded-t-lg py-2">
                <div className="flex justify-between items-center">
                    <div>
                        <CardTitle className="text-sm">{title}</CardTitle>
                        <p className="text-xs opacity-80">{date}</p>
                    </div>
                    <Badge variant="secondary">
                        {count} {countLabel}
                    </Badge>
                </div>
            </CardHeader>

            {/* Scrollable Content Area */}
            <CardContent className="pt-4 flex-1 overflow-y-auto max-h-[400px]">
                <div className="space-y-3">
                    {count > 0 ?
                        OPDSlotsForSelectedDate.map((slot, index) => (
                            <OPDSlotCard
                                key={slot.id || index}
                                slot={slot}
                                isSelected={selectedSlot?.id === slot.id}
                                onClick={() => onSlotClick(slot)}
                                onBookedClick={() => onSlotClick(slot)}
                            />
                        )) : (
                            <div className="text-center py-6 text-muted-foreground">
                                {emptyIcon}
                                <p className="text-sm">{emptyMessage}</p>
                                <p className="text-xs">{emptySubMessage}</p>
                            </div>
                        )}
                </div>
            </CardContent>

        </Card>
    )
}

export default DoctorOpdSchedule;