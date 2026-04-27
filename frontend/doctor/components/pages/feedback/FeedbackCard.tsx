import React from 'react';
import { Star } from 'lucide-react';
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { DoctorReview } from "@/types/home";
import { cn } from "@/lib/utils";

interface FeedbackCardProps {
    review: DoctorReview;
    className?: string;
    style?: React.CSSProperties;
}

export const FeedbackCard: React.FC<FeedbackCardProps> = ({ review, className, style }) => {
    // Helper to render stars
    const renderStars = (rating: number) => {
        return Array.from({ length: 5 }).map((_, i) => (
            <Star
                key={i}
                className={cn(
                    "w-3 h-3 sm:w-3.5 sm:h-3.5 md:w-4 md:h-4 mr-0.5",
                    i < Math.floor(rating)
                        ? "fill-yellow-400 text-yellow-400"
                        : "fill-gray-100 text-gray-200"
                )}
            />
        ));
    };

    return (
        <Card
            style={style}
            className={cn(
                "overflow-hidden border-none shadow-premium-sm hover:shadow-premium-md transition-all duration-300 bg-white/80 backdrop-blur-sm p-0 h-full",
                className
            )}
        >
            <CardContent className="p-3 sm:p-4 md:p-5 lg:p-6">
                {/* Header Section */}
                <div className="flex flex-col xs:flex-row xs:items-start justify-between gap-3 xs:gap-4 mb-3 sm:mb-4">
                    {/* Left - Patient Info */}
                    <div className="flex items-center gap-2 sm:gap-3 md:gap-4 min-w-0">
                        <Avatar className="w-10 h-10 sm:w-11 sm:h-11 md:w-12 md:h-12 border-2 border-primary/10 shrink-0">
                            <AvatarImage src={review.patient_image || ""} alt={review.patient_name} />
                            <AvatarFallback className="bg-primary/5 text-primary font-semibold text-sm sm:text-base">
                                {review.patient_name?.charAt(0) || "P"}
                            </AvatarFallback>
                        </Avatar>

                        <div className="min-w-0 flex-1">
                            <h4 className="font-semibold text-gray-900 leading-tight text-sm sm:text-base truncate">
                                {review.patient_name || "Anonymous Patient"}
                            </h4>
                            <div className="flex flex-wrap items-center gap-1 sm:gap-2 mt-0.5 sm:mt-1">
                                <span className="text-[10px] sm:text-xs text-muted-foreground truncate">
                                    {review.patient_location || 'Anonymous'}
                                </span>
                                <span className="w-0.5 h-0.5 sm:w-1 sm:h-1 rounded-full bg-gray-300" />
                                <span className="text-[10px] sm:text-xs text-muted-foreground whitespace-nowrap">
                                    {review.created_at}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Right - Rating */}
                    <div className="flex flex-row xs:flex-col items-center xs:items-end justify-between xs:justify-start gap-1 xs:gap-0 shrink-0">
                        <div className="flex">
                            {renderStars(review.rating)}
                        </div>
                        <span className="text-[10px] sm:text-xs font-medium text-gray-400">
                            {review.rating % 1 === 0 ? review.rating : review.rating.toFixed(1)} / 5
                        </span>
                    </div>
                </div>

                {/* Content Section */}
                <div className="space-y-1.5 sm:space-y-2">
                    {review.title && (
                        <h5 className="font-bold text-gray-800 text-xs sm:text-sm italic line-clamp-2">
                            "{review.title}"
                        </h5>
                    )}
                    <p className="text-xs sm:text-sm text-gray-600 leading-relaxed line-clamp-3 sm:line-clamp-4">
                        {review.content || "No review content provided."}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
};