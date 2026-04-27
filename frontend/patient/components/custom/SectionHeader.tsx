// import { ArrowRight } from "lucide-react";
// import { Button } from "../ui";

import { ArrowRight } from "lucide-react";
import { Button } from "../ui";
import { cn } from "@/lib/utils";

// interface SectionHeaderProps {
//     title: string;
//     showAction?: boolean; // control button visibility
//     actionText?: string;
//     onActionClick?: () => void;
// }

// export function SectionHeader({
//     title,
//     showAction = false,
//     actionText = "View All",
//     onActionClick,
// }: SectionHeaderProps) {
//     return (
//         <div className="flex items-center justify-between gap-4 mb-5">
//             <h2 className="text-[24px] font-bold text-primary font-headline tracking-tight">
//                 {title}
//             </h2>

//             {showAction && (
//                 <Button
//                     variant="ghost"
//                     onClick={onActionClick}
//                     className="text-primary font-medium hover:underline flex items-center gap-1 text-sm shrink-0"
//                 >
//                     {actionText}
//                     <ArrowRight className="w-3 h-3" />
//                 </Button>
//             )}
//         </div>
//     );
// }


interface SectionHeaderProps {
    title: string;
    subtitle?: string;
    showAction?: boolean;
    actionText?: string;
    onActionClick?: () => void;
    headingClassName?: string;
}

export function SectionHeader({
    title,
    subtitle,
    showAction = false,
    actionText = "Show All",
    onActionClick,
    headingClassName,
}: SectionHeaderProps) {
    return (
        <div className="mb-5 flex items-start justify-between gap-4">

            {/* Left Content */}
            <div>
                <h2
                    className={cn(
                        "text-[24px] font-bold text-primary font-headline tracking-tight",
                        headingClassName
                    )}
                >
                    {title}
                </h2>

                {subtitle && (
                    <p className="mt-1 text-base text-on-surface-variant">
                        {subtitle}
                    </p>
                )}
            </div>

            {/* Right Action */}
            {showAction && (
                <Button
                    variant="ghost"
                    onClick={onActionClick}
                    className="text-primary font-bold hover:underline flex items-center gap-1 text-sm shrink-0"
                >
                    {actionText}
                    <ArrowRight className="w-3 h-3" />
                </Button>
            )}
        </div>
    );
}