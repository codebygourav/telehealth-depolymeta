// import { ArrowRight } from "lucide-react";
// import { Button } from "../ui";

import { ArrowRight } from "lucide-react";
import { Button } from "../ui";
import { cn } from "@/lib/utils";
import { ChevronRight } from "lucide-react";


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
        <div className="mb-5 flex items-center justify-between gap-4 pb-5">

            {/* Left Content */}
            <div>
                <h2
                    className={cn(
                        "text-[24px] font-bold text-foreground font-headline tracking-tight",
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
                    className="text-white font-bold hover-bg-light-gray bg-primary flex items-center gap-1 text-xs py-1.5 px-2.5 shrink-0"
                >
                    {actionText}
                    <ChevronRight className="size-4" />
                </Button>
            )}
        </div>
    );
}