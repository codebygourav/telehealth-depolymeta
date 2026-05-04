import type { ReactNode } from "react";

import { cn } from "@/lib/utils";
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/components/ui";

interface EmptyStateProps {
    title: ReactNode;
    description?: ReactNode;
    icon?: ReactNode;
    button?: ReactNode;
    extra?: ReactNode;
    className?: string;
}

export function EmptyState({ title, description, icon, button, extra, className }: EmptyStateProps) {
    return (
        <Empty
            className={cn(
                "py-4 lg:py-20 px-6 bg-light-gray global-radius g-border container-max-width mx-auto w-full",
                className,
            )}
        >
            <EmptyHeader className="max-w-2xl">
                {icon ? (
                    <EmptyMedia className="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-white text-on-surface-variant/30 [&_svg]:size-8">
                        {icon}
                    </EmptyMedia>
                ) : null}

                <EmptyTitle className="text-xl font-bold text-primary">{title}</EmptyTitle>

                {description ? (
                    <EmptyDescription className="text-on-surface-variant">{description}</EmptyDescription>
                ) : null}
            </EmptyHeader>

            {extra || button ? (
                <EmptyContent className="max-w-2xl">
                    {extra}
                    {button}
                </EmptyContent>
            ) : null}
        </Empty>
    );
}
