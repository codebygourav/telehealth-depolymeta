import { Star } from 'lucide-react';
import type { DoctorReviewItem } from '@/types/doctor-details';

interface ReviewCardProps {
  review: DoctorReviewItem;
}

const ReviewCard = ({ review }: ReviewCardProps) => {
  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  return (
    <div className="bg-surface-container-lowest rounded-2xl p-6 shadow-sm border border-outline-variant/10">
      <div className="flex items-start gap-4">
        <img
          src={review.patient_image}
          alt={review.patient_name}
          className="w-12 h-12 rounded-full object-cover"
          referrerPolicy="no-referrer"
        />
        <div className="flex-1">
          <div className="flex items-center justify-between flex-wrap gap-2 mb-2">
            <h4 className="font-bold text-primary">{review.patient_name}</h4>
            <div className="flex items-center gap-1">
              <Star className="w-4 h-4 text-amber-500 fill-current" />
              <span className="text-sm font-medium">{review.rating}</span>
            </div>
          </div>
          <p className="text-xs text-on-surface-variant mb-3">
            {review.created_at}
          </p>
          <h5 className="font-semibold text-on-surface mb-2">{review.title}</h5>
          <p className="text-on-surface-variant text-sm leading-relaxed">
            {review.content}
          </p>
        </div>
      </div>
    </div>
  );
};

export default ReviewCard;