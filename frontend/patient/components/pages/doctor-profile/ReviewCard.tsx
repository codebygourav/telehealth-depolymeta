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
        <div className="rounded-lg p-5 border border-[#E7E8EB] shadow-[0px_2px_4px_0px_#0000001A]">
            <div className="flex items-start gap-3">
                <img
                    src={review.patient_image}
                    alt={review.patient_name}
                    className="w-12 h-12 rounded-full object-cover"
                    referrerPolicy="no-referrer"
                />
                <div className="flex-1">
                    <div className="flex items-center justify-between flex-wrap">
                        <h4 className="text-[#1F1E1E] text-lg font-[#1F1E1E] font-semibold">{review.patient_name}</h4>
                        <div className="flex items-center gap-1 bg-[#055BD9]/8 text-[#055BD9] px-2 py-1.5 rounded h-fit">
                            <Star size={12} fill="#055BD9" color="#055BD9" />
                            <span className="text-xs font-semibold">{review.rating}</span>
                        </div>
                    </div>
                    <p className="text-xs mb-2 text-[#4D4D4D]">
                        {review.created_at}
                    </p>
                    <h5 className="font-medium text-[#1F1E1E]">{review.title}</h5>
                    <p className="text-sm leading-relaxed text-[#4D4D4D]">
                        {review.content}
                    </p>
                </div>
            </div>
        </div>
    );
};

export default ReviewCard;