// import { ArrowRight } from "lucide-react";
// import { Button } from "../ui";

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
    subtitleClassName?: string;
    containerClassName?: string;
}

export function SectionHeader({
    title,
    subtitle,
    showAction = false,
    actionText = "Show All",
    onActionClick,
    headingClassName,
    subtitleClassName,
    containerClassName,
}: SectionHeaderProps) {
    return (
        <div className={cn("grid grid-cols-1 md:grid-cols-2 justify-between gap-4 pb-5 mb-5 ", containerClassName)}>

            {/* Left Content */}
            <div>
                <div className="flex items-start justify-between gap-2">
                    <h1
                        className={cn(
                            "font-bold text-foreground font-headline tracking-tight",
                            headingClassName
                        )}
                    >
                        {title}
                    </h1>
                    {showAction && (
                        <button
                            type="button"
                            onClick={onActionClick}
                            className="md:hidden flex items-center gap-2  text-primary justify-between mt-2"
                            aria-label={actionText}
                        >
                            <ChevronRight className="size-6 ml-0" />
                        </button>
                    )}
                </div>

                {subtitle && (
                    <h4 className={cn("mt-1 text-on-surface-variant", subtitleClassName)}>
                        {subtitle}
                    </h4>
                )}
            </div>

            {/* Right Action */}
            {showAction && (
                <div className="flex justify-end text-end">
                    <Button
                        onClick={onActionClick}
                        className="hidden md:flex items-center w-auto h-10 gap-1 ml-0 btn-primary-cta"
                    >
                        {actionText}
                        <ChevronRight className="size-4.5 ml-0" />
                    </Button>

                </div>
            )}
        </div>
    );
}
