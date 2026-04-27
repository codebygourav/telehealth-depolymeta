"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { useMyReviews } from "@/queries/useMyReviews";
import { Star, Loader2 } from "lucide-react";
import { ReviewResponse } from "@/types/reviews";

export default function Page() {

    const [page, setPage] = useState(1);

    const { data, isLoading, isError, isFetching } = useMyReviews(page);
    const reviews = (data as ReviewResponse | undefined)?.data ?? [];
    const pagination = (data as ReviewResponse | undefined)?.pagination;

    // Loading State
    if (isLoading) {
        return (
            <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10 md:py-12">
                <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4">
                    <Loader2 className="w-8 h-8 sm:w-10 sm:h-10 text-primary animate-spin" />
                    <p className="text-muted-foreground text-sm sm:text-base">Loading your reviews...</p>
                </div>
            </div>
        );
    }

    // console.log("Pagination data:", pagination);
    // console.log("Reviews data:", data);


    // Error State
    if (isError) {
        return (
            <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10 md:py-12">
                <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4">
                    <div className="p-4 bg-red-50 dark:bg-red-950/30 rounded-full">
                        <Star className="w-8 h-8 sm:w-10 sm:h-10 text-red-500" />
                    </div>
                    <p className="text-red-500 text-sm sm:text-base">Error loading reviews. Please try again.</p>
                    <Button
                        variant="outline"
                        onClick={() => window.location.reload()}
                        className="mt-2"
                    >
                        Retry
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="max-w-7xl mx-auto px-0 sm:px-6 md:px-8 lg:px-10 py-6 sm:py-8 md:py-10 lg:py-12">
            {/* Header Section */}
            <div className="mb-6 sm:mb-8 md:mb-10 lg:mb-12">
                <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold text-primary">
                    My Reviews
                </h1>
                <p className="text-sm sm:text-base text-muted-foreground mt-1 sm:mt-2">
                    Manage and view all your doctor reviews.
                </p>
            </div>

            {/* Empty State */}
            {!isLoading && reviews.length === 0 && (
                <div className="text-center py-12 sm:py-16 md:py-20 px-4 border-2 border-dashed rounded-xl sm:rounded-2xl bg-gray-50/30 dark:bg-gray-950/10">
                    <div className="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                        <Star className="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
                    </div>
                    <h3 className="text-lg sm:text-xl font-semibold text-muted-foreground mb-2">
                        No Reviews Found
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        You haven't written any reviews yet.
                    </p>
                </div>
            )}

            {/* Reviews List */}
            {!isLoading && reviews.length > 0 && (
                <>
                    <div className="space-y-6 sm:space-y-8 md:space-y-10  shadow-sm bg-white p-4 rounded-xl sm:rounded-2xl">
                        {reviews.map((review, index) => (
                            <div
                                key={review.id}
                                className={` border-outline-variant/20  ${index !== reviews.length - 1 ? 'border-b' : ''
                                    }`}
                            >
                                {/* Review Header */}
                                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 sm:gap-6">
                                    {/* Doctor Info - Left Side */}
                                    <div className="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                                        <img
                                            src={review.doctor_avatar}
                                            alt={review.doctor_name}
                                            className="w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 rounded-full object-cover ring-2 ring-primary/10 shadow-md"
                                            referrerPolicy="no-referrer"
                                        />

                                        <div className="flex-1 min-w-0">
                                            <h2 className="text-base sm:text-lg md:text-xl font-semibold text-primary truncate">
                                                Dr. {review.doctor_name}
                                            </h2>
                                            <p className="text-xs sm:text-sm text-muted-foreground truncate">
                                                {review.doctor_departments}
                                            </p>

                                            
                                        </div>
                                    </div>

                                    {/* Rating & Date - Right Side */}
                                    <div className="text-left sm:text-right shrink-0">
                                        <div className="flex gap-0.5 sm:gap-1 justify-start sm:justify-end">
                                            {[...Array(5)].map((_, i) => (
                                                <Star
                                                    key={i}
                                                    className={`w-4 h-4 sm:w-5 sm:h-5 ${i < review.rating
                                                            ? 'text-yellow-500 fill-yellow-500'
                                                            : 'text-gray-300 fill-gray-300 dark:text-gray-600 dark:fill-gray-600'
                                                        }`}
                                                />
                                            ))}
                                        </div>
                                        <p className="text-xs sm:text-sm font-medium text-muted-foreground mt-1 sm:mt-2">
                                            Visited: {review.created_at}
                                        </p>
                                    </div>
                                </div>

                                {/* Review Content */}
                                <div className="mt-4 sm:mt-5 md:mt-6">
                                    <h3 className="text-base sm:text-lg md:text-xl font-bold text-primary mb-2 sm:mb-3">
                                        {review.title}
                                    </h3>
                                    <p className="text-sm sm:text-base md:text-lg text-muted-foreground italic leading-relaxed whitespace-pre-line">
                                        "{review.content}"
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Pagination Section */}
                    {pagination && pagination.last_page > 1 && (
                        <div className="flex flex-col sm:flex-row justify-center sm:justify-end items-center gap-3 sm:gap-4 mt-8 sm:mt-10 md:mt-12">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={page === 1 || isFetching}
                                onClick={() => setPage((prev) => prev - 1)}
                                className="w-24 sm:w-auto"
                            >
                                Previous
                            </Button>

                            <div className="flex items-center gap-2">
                                <span className="text-xs sm:text-sm text-muted-foreground">
                                    Page {pagination.current_page} of {pagination.last_page}
                                </span>
                                {isFetching && (
                                    <Loader2 className="w-3 h-3 sm:w-4 sm:h-4 animate-spin text-muted-foreground" />
                                )}
                            </div>

                            <Button
                                variant="outline"
                                size="sm"
                                disabled={page === pagination.last_page || isFetching}
                                onClick={() => setPage((prev) => prev + 1)}
                                className="w-24 sm:w-auto"
                            >
                                Next
                            </Button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}