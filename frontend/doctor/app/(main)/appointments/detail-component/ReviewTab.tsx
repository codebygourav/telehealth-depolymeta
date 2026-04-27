import { FeedbackCard } from "@/components/pages/feedback/FeedbackCard";
import {  ArrowRight } from "lucide-react";
import Link from "next/link";

export default function ReviewTab({ appointment }: { appointment: any }) {

    const reviewData = appointment?.doctor?.review;
    console.log("reviewData", appointment);

    const formattedReview = reviewData
        ? {
            id: reviewData?.id || String(Math.random()),
            patient_name: appointment?.patient?.name || "Anonymous",
            patient_image: appointment?.patient?.avatar || "",
            patient_age: appointment?.patient?.age || "",
            patient_location: appointment?.patient?.city || "India",
            rating: reviewData?.rating || 0,
            title: reviewData?.title || "",
            content: reviewData?.content || "",
            total_reviews: 0,
            doctor_name: appointment?.doctor?.name || "",
            doctor_avatar: appointment?.doctor?.avatar || "",
            doctor_experience: "",
            doctor_departments: "",
            rating_stars: String(reviewData?.rating || 0),
            created_at: reviewData?.created_at || "",
        }
        : null;

    return (
        <div className="space-y-6">

            {!formattedReview ? (
                <div className="text-center py-10 text-muted-foreground">
                    No review available
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">

                    {/* 🔹 Card Wrapper */}
                    <div className="space-y-3 relative">

                        <FeedbackCard review={formattedReview} />

                        {/* ✅ View Button (only here) */}
                        <Link href="/feedbacks"
                            className="absolute md:top-28 top-22 md:right-6 right-3 bg-primary text-white rounded-full p-1"
                        >
                            <ArrowRight className="h-4 w-4 md:h-5 md:w-5" />
                        </Link>
                    </div>

                </div>
            )}

        </div>
    );
}