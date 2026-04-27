import { Button } from "@/components/ui/button";
import { ReactNode } from "react";

interface SectionHeaderProps {
  title: string;
  description?: string;
  actionLabel?: string;
  actionIcon?: ReactNode;
  onAction?: () => void;
}

export function SectionHeader({
  title,
  description,
  actionLabel,
  actionIcon,
  onAction,
}: SectionHeaderProps) {
  return (
    <div className="flex justify-between items-center mb-4">
      <div>
        <h3>{title}</h3>
        {description && (
          <p className="text-sm text-muted-foreground">{description}</p>
        )}
      </div>

      {actionLabel && (
        <Button className="bg-primary hover:bg-primary/90" onClick={onAction}>
          {actionIcon}
          {actionLabel}
        </Button>
      )}
    </div>
  );
}