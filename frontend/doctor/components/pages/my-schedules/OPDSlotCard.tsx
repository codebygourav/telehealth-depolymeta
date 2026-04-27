import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import { Video, Edit } from "lucide-react";
import { OPDSlot } from "@/types/schedule";


interface OPDSlotCardProps {
    slot: OPDSlot;
    isSelected: boolean;
    onClick: () => void;
    onBookedClick: () => void;
    onEdit?: () => void;
}

export const OPDSlotCard = ({
    slot,
    isSelected,
    onClick,
    onBookedClick,
    onEdit
}: OPDSlotCardProps) => {
    return (
        <div
            className={`border rounded-lg p-3 hover:shadow-md transition-all mb-2 cursor-pointer ${isSelected ? 'border-primary bg-primary/5' : 'border-border'
                }`}
            onClick={onClick}
        >

            <div className="flex items-center justify-between mb-2">
                <span className="font-medium text-sm">{slot.time_range}</span>
            </div>

            <div className="flex items-center gap-2 text-xs text-muted-foreground mb-2">
                <Video className="h-3 w-3" />
                <span>{slot.consultation_type_label}</span>
            </div>

            <div className="flex items-center justify-between mt-2">
                <div
                    className="cursor-pointer hover:opacity-80 transition-opacity"
                    onClick={(e) => {
                        e.stopPropagation();
                        onBookedClick();
                    }}
                >
                    <Badge variant="outline" className="text-xs font-normal">
                        {slot.booked_count || 0}/{slot.slot_capacity} booked
                    </Badge>
                </div>
                {onEdit && (
                    <Button variant="ghost" size="sm" className="h-6 px-2">
                        <Edit className="h-3 w-3 mr-1" />
                        <span className="text-xs">Edit</span>
                    </Button>
                )}
            </div>
        </div>
    );
};