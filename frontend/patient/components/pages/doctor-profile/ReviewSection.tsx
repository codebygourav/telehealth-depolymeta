import type { DoctorReviewItem } from '@/types/doctor-details';
import ReviewCard from './ReviewCard';

interface ReviewSectionProps {
  reviews: DoctorReviewItem[];
}

const ReviewSection = ({ reviews }: ReviewSectionProps) => {
  if (!reviews || reviews.length === 0) {
    return (
      <div className="text-center py-12 bg-surface-container-lowest rounded-3xl">
        <p className="text-on-surface-variant">No reviews yet.</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {reviews.map((review) => (
        <ReviewCard key={review.id} review={review} />
      ))}
    </div>
  );
};

export default ReviewSection;