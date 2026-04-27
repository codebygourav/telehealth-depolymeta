import { FeedbackCard } from "@/components/pages/feedback/FeedbackCard";
import { DoctorReview } from "@/types/home";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Star } from "lucide-react";

interface ReviewItem {
  id: number;
  patient: string;
  rating: number;
  date: string;
  comment: string;
  patient_image: string | null;
}

interface ReviewsSectionProps {
  reviews: ReviewItem[];
  averageRating: string;
}

export default function ReviewsSection({
  reviews,
  averageRating,
}: ReviewsSectionProps) {
  // Convert ReviewItem to DoctorReview format for FeedbackCard
  const doctorReviews: DoctorReview[] = reviews.map((review) => ({
    id: review.id.toString(),
    patient_name: review.patient,
    patient_image: review.patient_image,
    patient_age: "",
    patient_location: null,
    title: "",
    content: review.comment,
    rating: review.rating,
    total_reviews: reviews.length,
    doctor_name: "",
    doctor_avatar: null,
    doctor_experience: "",
    doctor_departments: "",
    rating_stars: averageRating,
    created_at: review.date,
  }));

  return (
    <Card className="border-border">
      <CardHeader>
        <CardTitle>Patient Feedback</CardTitle>
        <CardDescription>
          Average rating: {averageRating} out of 5 ({reviews.length} reviews)
        </CardDescription>
      </CardHeader>

      <CardContent>
        {reviews.length === 0 ? (
          <div className="text-center py-8">
            <p className="text-muted-foreground">No reviews available yet.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {reviews.map((review, index) => (
              <FeedbackCard
                key={review.id || index}
                review={doctorReviews[index]}
                className="animate-in fade-in slide-in-from-bottom-4 duration-500 fill-mode-both h-full"
                style={{ animationDelay: `${index * 50}ms` }}
              />
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}