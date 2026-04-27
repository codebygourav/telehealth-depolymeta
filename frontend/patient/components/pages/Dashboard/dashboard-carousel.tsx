"use client";

import * as React from "react";
import {
  Carousel,
  CarouselApi,
  CarouselContent,
  CarouselItem,
} from "@/components/ui/carousel";
import { cn } from "@/lib/utils";

interface DashboardCarouselProps<T> {
  items: T[];
  renderItem: (item: T, index: number) => React.ReactNode;
  basisClassName?: string;
  className?: string;
  contentClassName?: string;
  dotClassName?: string;
  activeDotClassName?: string;
  dotsContainerClassName?: string;
  opts?: any;
}

/**
 * A reusable Carousel component for Dashboard sections
 * Handles standard spacing, shadows, and pagination dots
 */
export function DashboardCarousel<T>({
  items,
  renderItem,
  basisClassName = "basis-full sm:basis-1/2 md:basis-1/3",
  className,
  contentClassName,
  dotClassName,
  activeDotClassName,
  dotsContainerClassName,
  opts = { align: "start", loop: false },
}: DashboardCarouselProps<T>) {
  const [api, setApi] = React.useState<CarouselApi>();
  const [current, setCurrent] = React.useState(0);
  const [scrollSnaps, setScrollSnaps] = React.useState<number[]>([]);

  React.useEffect(() => {
    if (!api) return;

    setScrollSnaps(api.scrollSnapList());
    setCurrent(api.selectedScrollSnap());

    const updateState = () => {
      setCurrent(api.selectedScrollSnap());
    };

    api.on("select", updateState);
    api.on("reInit", () => {
      setScrollSnaps(api.scrollSnapList());
      updateState();
    });

    return () => {
      api.off("select", updateState);
    };
  }, [api]);

  if (!items || items.length === 0) return null;

  return (
    <div className={cn("w-full", className)}>
      <Carousel opts={opts} setApi={setApi} className="w-full">
        <CarouselContent className={cn("-ml-4 md:-ml-8 py-4 px-1", contentClassName)}>
          {items.map((item, index) => (
            <CarouselItem
              key={index}
              className={cn("pl-4 md:pl-8", basisClassName)}
            >
              {renderItem(item, index)}
            </CarouselItem>
          ))}
        </CarouselContent>
      </Carousel>

      {scrollSnaps.length > 1 && (
        <div
          className={cn(
            "flex justify-center items-center gap-2 mt-6",
            dotsContainerClassName
          )}
        >
          {scrollSnaps.map((_, i) => (
            <button
              key={i}
              onClick={() => api?.scrollTo(i)}
              className={cn(
                "h-2 rounded-full transition-all duration-300",
                i === current
                  ? cn("w-4 bg-primary opacity-100", activeDotClassName)
                  : cn("w-2 bg-primary/20 hover:bg-primary/40", dotClassName)
              )}
              aria-label={`Go to slide ${i + 1}`}
            />
          ))}
        </div>
      )}
    </div>
  );
}
