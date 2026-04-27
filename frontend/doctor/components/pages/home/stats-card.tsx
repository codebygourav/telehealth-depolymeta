import { TrendingUp, Users } from "lucide-react";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

interface StatsCardProps {
    title: string;
    value: number | string;
    badgeText?: string;
    icon?: React.ReactNode;
    subTitle?: string;
}

export default function StatsCard({
    title,
    value,
    badgeText = "Patient base",
    icon,
    subTitle,
}: StatsCardProps) {
    return (
        <Card className="border-border overflow-hidden h-full">
            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <CardTitle className="text-[11px] sm:text-xs md:text-sm font-medium text-muted-foreground wrap-break-word leading-tight">
                    {title}
                </CardTitle>
                {icon && <div className="h-3 w-3 sm:h-4 sm:w-4 shrink-0 text-[#0f5132] ml-2">{icon}</div>}
            </CardHeader>
            <CardContent className="p-3 sm:p-4 pt-0">
                <div className="text-lg sm:text-xl md:text-2xl font-bold">
                    {value}
                </div>
                <p className="mt-1 flex items-start gap-1 text-[9px] sm:text-xs text-muted-foreground">
                    {/* <TrendingUp className="h-2.5 w-2.5 sm:h-3 sm:w-3 text-primary shrink-0" /> */}
                    <span>{subTitle}</span>
                </p>
            </CardContent>
        </Card>
    );
}