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
    iconBgColor?: string;
    progress?: string;
    progressBgColor?: string;
}

export default function StatsCard({
    title,
    value,
    badgeText = "Patient base",
    icon,
    subTitle,
    iconBgColor,
    progress,
    progressBgColor,
}: StatsCardProps) {
    return (
        <div className="border-light-gray p-5 rounded-lg shadow-[0px_2px_4px_0px_#0000001A] overflow-hidden h-full">
            <div className="flex flex-row items-center justify-between space-y-0">
                <div className="text-[#373737] text-sm">
                    {title}
                </div>
                {icon &&
                    <div className="h-10 w-10 rounded-md flex items-center justify-center"
                        style={{ backgroundColor: iconBgColor || "" }}
                    >
                        {icon}
                    </div>
                }
            </div>
            <div className="text-[#1F1E1E] font-semibold text-lg mt-4">
                {value}
            </div>
            <div className="text-[#4D4D4D] text-sm">
                Next dose in 2 hours
            </div>
            <div className="mt-5">

                {/* progress bar */}
                <div className="w-full h-1 bg-[#E7E8EB]">

                    <div className="h-[3px]"
                        style={{ width: progress, backgroundColor: progressBgColor }}
                    ></div>
                </div>

                <p className="mt-3 text-[#373737] text-xs">
                    <span>{subTitle}</span>
                </p>
            </div>

        </div>
    );
}