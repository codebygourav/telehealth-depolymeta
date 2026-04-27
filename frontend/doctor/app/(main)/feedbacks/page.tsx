"use client";

import React, { useState } from "react";
import { useFeedbacks } from "@/queries/useFeedbacks";
import { FeedbackCard } from "@/components/pages/feedback/FeedbackCard";
import PaginationControls from "@/components/pagination/PaginationControls";
import { MessageSquare, Star, AlertCircle } from "lucide-react";
import { Button, Skeleton } from "@/components/ui";

export default function FeedbacksPage() {
    const [page, setPage] = useState(1);
    const { data, isLoading, isError, refetch } = useFeedbacks(page);

    const reviews = data?.data || [];
    const pagination = data?.pagination;
    const rawAverage = reviews.length > 0
        ? (reviews.reduce((acc, curr) => acc + curr.rating, 0) / reviews.length)
        : 0;
    const averageRating = rawAverage % 1 === 0 ? rawAverage : rawAverage.toFixed(1);

    return (
        <div className="min-h-screen bg-slate-50/30 md:px-4">
            {/* Header Section */}
            <div className="flex flex-col lg:flex-row lg:items-end justify-between gap-4 sm:gap-6 mb-6 sm:mb-8 md:mb-12">
                <div className="space-y-2 sm:space-y-3 md:space-y-4">
                    <div className="inline-flex items-center gap-1.5 sm:gap-2 px-2 sm:px-3 py-0.5 sm:py-1 rounded-full bg-primary/10 text-primary border border-primary/10">
                        <MessageSquare className="w-3 h-3 sm:w-4 sm:h-4" />
                        <span className="text-[10px] sm:text-xs font-semibold uppercase tracking-wider">Patient Testimonials</span>
                    </div>
                    <h1 className="text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">
                        Patient Feedbacks & Reviews
                    </h1>
                    <p className="text-sm sm:text-base md:text-lg text-muted-foreground leading-relaxed max-w-2xl">
                        See what your patients are saying about their experiences with you.
                    </p>
                </div>

                {/* Average Rating Card - Mobile Optimized */}
                <div className="flex flex-row lg:flex-col items-center justify-between lg:items-end gap-3 sm:gap-4 bg-white/60 backdrop-blur-md p-3 sm:p-4 md:p-6 rounded-2xl sm:rounded-3xl border border-white shadow-premium-sm w-full lg:w-auto">
                    <div className="flex items-center gap-2 sm:gap-3">
                        <div className="flex">
                            {Array.from({ length: 5 }).map((_, i) => (
                                <Star
                                    key={i}
                                    className={`w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 ${i < Math.floor(Number(averageRating)) ? "fill-yellow-400 text-yellow-400" : "fill-gray-100 text-gray-200"}`}
                                />
                            ))}
                        </div>
                        <span className="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{averageRating}</span>
                    </div>
                    <p className="text-[10px] sm:text-xs font-medium text-muted-foreground uppercase tracking-widest italic">
                        Average Rating
                    </p>
                </div>
            </div>

            {/* Main Content */}
            <div className="relative pb-12 sm:pb-16 md:pb-20">
                {isLoading ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 md:gap-6 lg:gap-8">
                        {[1, 2, 3, 4, 5, 6].map((n) => (
                            <div key={n} className="bg-white p-4 sm:p-5 md:p-6 rounded-2xl sm:rounded-3xl shadow-sm border border-gray-100">
                                <div className="flex items-center gap-3 sm:gap-4 mb-3 sm:mb-4">
                                    <Skeleton className="w-10 h-10 sm:w-12 sm:h-12 rounded-full" />
                                    <div className="space-y-1.5 sm:space-y-2">
                                        <Skeleton className="h-3 sm:h-4 w-24 sm:w-32" />
                                        <Skeleton className="h-2 sm:h-3 w-16 sm:w-24" />
                                    </div>
                                </div>
                                <Skeleton className="h-3 sm:h-4 w-full mb-1.5 sm:mb-2" />
                                <Skeleton className="h-3 sm:h-4 w-[80%] mb-3 sm:mb-4" />
                                <div className="flex gap-0.5 sm:gap-1">
                                    {[1, 2, 3, 4, 5].map(s => <Skeleton key={s} className="w-3 h-3 sm:w-4 sm:h-4 rounded-full" />)}
                                </div>
                            </div>
                        ))}
                    </div>
                ) : isError ? (
                    <div className="flex flex-col items-center justify-center py-12 sm:py-16 md:py-20 bg-white/50 backdrop-blur-sm rounded-2xl sm:rounded-3xl border border-dashed border-red-200 text-center px-4 sm:px-6">
                        <div className="w-12 h-12 sm:w-16 sm:h-16 bg-red-50 rounded-full flex items-center justify-center mb-4 sm:mb-6">
                            <AlertCircle className="w-6 h-6 sm:w-8 sm:h-8 text-red-500" />
                        </div>
                        <h3 className="text-lg sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2">Oops! Something went wrong</h3>
                        <p className="text-xs sm:text-sm text-muted-foreground mb-4 sm:mb-8 max-w-sm">
                            We couldn't load your feedbacks at this moment. Let's try again.
                        </p>
                        <Button
                            onClick={() => refetch()}
                            variant="outline"
                            className="rounded-full px-6 sm:px-8 md:px-12 hover:bg-primary/5 hover:text-primary transition-all active:scale-95 text-xs sm:text-sm h-9 sm:h-10"
                        >
                            Retry Connection
                        </Button>
                    </div>
                ) : reviews.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-16 sm:py-20 md:py-24 bg-white/40 backdrop-blur-md rounded-2xl sm:rounded-3xl border border-dashed border-gray-200 px-4">
                        <div className="w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 bg-primary/5 rounded-full flex items-center justify-center mb-4 sm:mb-6 md:mb-8">
                            <MessageSquare className="w-7 h-7 sm:w-8 sm:h-8 md:w-10 md:h-10 text-primary/40" />
                        </div>
                        <h3 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2 sm:mb-3 text-center px-4">No reviews yet</h3>
                        <p className="text-xs sm:text-sm text-muted-foreground max-w-sm text-center px-4 leading-relaxed">
                            When patients complete their appointments and leave feedback, they will appear here. Keep up the great work!
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 md:gap-6 lg:gap-8">
                            {reviews.map((review, index) => (
                                <FeedbackCard
                                    key={review.id || index}
                                    review={review}
                                    className="animate-in fade-in slide-in-from-bottom-4 duration-500 fill-mode-both h-full"
                                    style={{ animationDelay: `${index * 50}ms` }}
                                />
                            ))}
                        </div>

                        {pagination && pagination.last_page > 1 && (
                            <div >
                                <PaginationControls
                                    currentPage={pagination.current_page}
                                    totalPages={pagination.last_page}
                                    totalItems={pagination.total}
                                    itemsPerPage={pagination.per_page}
                                    onPageChange={(p) => {
                                        setPage(p);
                                        window.scrollTo({ top: 0, behavior: 'smooth' });
                                    }}
                                />
                            </div>
                        )}
                    </>
                )}
            </div>

            {/* Background Micro-Decoration - Hidden on mobile */}
            <div className="hidden md:block fixed -top-24 -right-24 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none -z-10" />
            <div className="hidden md:block fixed -bottom-48 -left-48 w-[32rem] h-[32rem] bg-indigo-50/30 rounded-full blur-[120px] pointer-events-none -z-10" />
        </div>
    );
}