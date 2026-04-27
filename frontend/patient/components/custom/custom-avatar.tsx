"use client";

import {
  Avatar,
  AvatarBadge,
  AvatarFallback,
  AvatarImage,
} from "@/components/ui/avatar";

import { cn } from "@/lib/utils";

type Size = "xs" | "sm" | "default" | "lg" | "xl";
type Radius = "none" | "sm" | "md" | "lg" | "full";

type CustomAvatarProps = {
  src?: string;
  alt?: string;
  name?: string;
  fallback?: string;
  size?: Size;
  radius?: Radius;
  status?: "online" | "offline" | "busy";
  className?: string;
};

const sizeMap: Record<Size, string> = {
  xs: "size-5 text-[10px]",
  sm: "size-6 text-xs",
  default: "size-8 text-sm",
  lg: "size-10 text-base",
  xl: "size-14 text-lg",
};

const radiusMap: Record<Radius, string> = {
  none: "rounded-none",
  sm: "rounded-sm",
  md: "rounded-md",
  lg: "rounded-lg",
  full: "rounded-full",
};

export function CustomAvatar({
  src,
  alt = "avatar",
  name,
  fallback,
  size = "default",
  radius = "none",
  status,
  className,
}: CustomAvatarProps) {
  const fallbackText = fallback || name?.charAt(0).toUpperCase() || "U";

  return (
    <Avatar
      size={size === "xs" ? "sm" : size === "xl" ? "lg" : size}
      className={cn(
        sizeMap[size],
        radiusMap[radius],
        "[&::after]:rounded-inherit",
        className,
      )}
    >
      <AvatarImage
        src={src}
        alt={alt}
        className={cn(radiusMap[radius], "rounded-inherit")}
      />

      <AvatarFallback className={cn(radiusMap[radius], "rounded-inherit")}>
        {fallbackText}
      </AvatarFallback>

      {status && (
        <AvatarBadge
          className={cn(
            "ring-2 ring-background",
            size === "xs" && "size-1.5",
            size === "sm" && "size-2",
            size === "default" && "size-2.5",
            size === "lg" && "size-3",
            size === "xl" && "size-3.5",
            status === "online" && "bg-green-500",
            status === "offline" && "bg-gray-400",
            status === "busy" && "bg-red-500",
          )}
        />
      )}
    </Avatar>
  );
}
