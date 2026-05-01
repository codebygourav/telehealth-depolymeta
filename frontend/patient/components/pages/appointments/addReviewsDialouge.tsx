// "use client"

// import { useState } from "react"
// import { Star } from "lucide-react"
// import {
//   Dialog,
//   DialogContent,
//   DialogDescription,
//   DialogFooter,
//   DialogHeader,
//   DialogTitle,
//   DialogTrigger,
// } from "@/components/ui/dialog"
// import { Button } from "@/components/ui/button"
// import { Textarea } from "@/components/ui/textarea"
// import { cn } from "@/lib/utils"

// interface AddReviewsDialogProps {
//   children?: React.ReactNode
//   onSubmit?: (review: { rating: number; comment: string }) => void
//   defaultOpen?: boolean
//   appointmentStatus?: string
//   hasExistingReview?: boolean
//   doctorName?: string
//   doctorId?: string
//   canSubmit?: boolean
//   appointmentId?: string
// }

// export default function AddReviewsDialouge({
//   children,
//   onSubmit,
//   defaultOpen = false,
//   appointmentStatus,
//   hasExistingReview = false,
//   doctorName,
//   doctorId,
//   canSubmit,
//   appointmentId,
// }: AddReviewsDialogProps) {
//   const [open, setOpen] = useState(defaultOpen)
//   const [rating, setRating] = useState(0)
//   const [hoveredRating, setHoveredRating] = useState(0)
//   const [comment, setComment] = useState("")
//   const [isSubmitting, setIsSubmitting] = useState(false)

//   const handleSubmit = async () => {
//     if (rating === 0 || !doctorId || !appointmentId) return

//     setIsSubmitting(true)

//     try {
//       const reviewData = {
//         doctor_id: doctorId,
//         title: "Review for " + (doctorName || "Doctor"),
//         content: comment,
//         rating: rating.toString(),
//         appointment_id: appointmentId
//       }

//       const response = await fetch('https://telehealthwebapplive.cmcludhiana.in/api/v2/reviews', {
//         method: 'POST',
//         headers: {
//           'Content-Type': 'application/json',
//         },
//         body: JSON.stringify(reviewData)
//       })

//       if (response.ok) {
//         const result = await response.json()
//         onSubmit?.(result)

//         // Reset form
//         setRating(0)
//         setComment("")
//         setOpen(false)
//       } else {
//         console.error('Failed to submit review:', response.statusText)
//       }
//     } catch (error) {
//       console.error('Error submitting review:', error)
//     } finally {
//       setIsSubmitting(false)
//     }
//   }

//   const handleCancel = () => {
//     setRating(0)
//     setComment("")
//     setOpen(false)
//   }

//   // Don't render anything if appointment is not completed, review already exists, or can't submit
//   if (!canSubmit || hasExistingReview || appointmentStatus !== 'completed') {
//     return children ? <>{children}</> : null
//   }

//   return (
//     <Dialog open={open} onOpenChange={setOpen}>
//       <DialogTrigger asChild>
//         {children || (
//           <div className="border border-gray-200 rounded-lg p-6 bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer">
//             <div className="text-center space-y-4">
//               <div className="flex justify-center">
//                 <div className="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
//                   <Star className="w-6 h-6 text-yellow-600" />
//                 </div>
//               </div>
//               <div>
//                 <h3 className="font-semibold text-gray-900 mb-2">
//                   You haven't added any review
//                 </h3>
//                 <p className="text-sm text-gray-600 mb-4">
//                   If you want to add a review for {doctorName || 'your doctor'} then add review like this
//                 </p>
//                 <Button variant="outline" className="w-full">
//                   Add Review
//                 </Button>
//               </div>
//             </div>
//           </div>
//         )}
//       </DialogTrigger>
//       <DialogContent className="sm:max-w-md">
//         <DialogHeader>
//           <DialogTitle>Add Your Review</DialogTitle>
//           <DialogDescription>
//             Share your experience by rating and leaving a review.
//           </DialogDescription>
//         </DialogHeader>

//         <div className="space-y-6">
//           {/* Rating Stars */}
//           <div className="space-y-2">
//             <label className="text-sm font-medium">Rating</label>
//             <div className="flex gap-1">
//               {[1, 2, 3, 4, 5].map((star) => (
//                 <button
//                   key={star}
//                   type="button"
//                   className="p-1 transition-colors"
//                   onClick={() => setRating(star)}
//                   onMouseEnter={() => setHoveredRating(star)}
//                   onMouseLeave={() => setHoveredRating(0)}
//                 >
//                   <Star
//                     className={cn(
//                       "h-6 w-6 transition-colors",
//                       star <= (hoveredRating || rating)
//                         ? "fill-yellow-400 text-yellow-400"
//                         : "text-gray-300"
//                     )}
//                   />
//                 </button>
//               ))}
//             </div>
//             {rating > 0 && (
//               <p className="text-sm text-muted-foreground">
//                 {rating === 1 && "Poor"}
//                 {rating === 2 && "Fair"}
//                 {rating === 3 && "Good"}
//                 {rating === 4 && "Very Good"}
//                 {rating === 5 && "Excellent"}
//               </p>
//             )}
//           </div>

//           {/* Review Comment */}
//           <div className="space-y-2">
//             <label htmlFor="review-comment" className="text-sm font-medium">
//               Your Review (Optional)
//             </label>
//             <Textarea
//               id="review-comment"
//               placeholder="Share details about your experience..."
//               value={comment}
//               onChange={(e) => setComment(e.target.value)}
//               className="min-h-[100px]"
//               maxLength={500}
//             />
//             <p className="text-xs text-muted-foreground text-right">
//               {comment.length}/500
//             </p>
//           </div>
//         </div>

//         <DialogFooter>
//           <Button variant="outline" onClick={handleCancel}>
//             Cancel
//           </Button>
//           <Button 
//             onClick={handleSubmit} 
//             disabled={rating === 0 || isSubmitting}
//           >
//             {isSubmitting ? "Submitting..." : "Submit Review"}
//           </Button>
//         </DialogFooter>
//       </DialogContent>
//     </Dialog>
//   )
// }

"use client";

import { useState } from "react";
import { ChevronRight, Star } from "lucide-react";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { cn } from "@/lib/utils";
import { useSubmitReview } from "@/queries/useSubmitReview";

interface AddReviewsDialogProps {
    children?: React.ReactNode;
    onSubmit?: (review: unknown) => void;
    defaultOpen?: boolean;
    appointmentStatus?: string;
    hasExistingReview?: boolean;
    doctorName?: string;
    doctorId?: string;
    canSubmit?: boolean;
    appointmentId?: string;
}

export default function AddReviewsDialouge({
    children,
    onSubmit,
    defaultOpen = false,
    appointmentStatus,
    hasExistingReview = false,
    doctorName,
    doctorId,
    canSubmit,
    appointmentId,
}: AddReviewsDialogProps) {
    const [open, setOpen] = useState(defaultOpen);
    const [rating, setRating] = useState(0);
    const [hoveredRating, setHoveredRating] = useState(0);
    const [comment, setComment] = useState("");

    const { mutate, isPending } = useSubmitReview();

    const resetForm = () => {
        setRating(0);
        setComment("");
    };

    const handleSubmit = () => {
        if (rating === 0 || !doctorId || !appointmentId) return;

        mutate(
            {
                doctor_id: doctorId,
                title: `Review for ${doctorName || "Doctor"}`,
                content: comment,
                rating: String(rating),
                appointment_id: appointmentId,
            },
            {
                onSuccess: (result) => {
                    onSubmit?.(result);
                    resetForm();
                    setOpen(false);
                },
                onError: (error) => {
                    console.error("Error submitting review:", error);
                },
            }
        );
    };

    const handleCancel = () => {
        resetForm();
        setOpen(false);
    };

    if (!canSubmit || hasExistingReview || appointmentStatus !== "completed") {
        return children ? <>{children}</> : null;
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>

            <DialogTrigger asChild>
                {children || (
                    <div className="border border-gray-200 rounded-lg p-6 cursor-pointer mt-5">
                        <div className="text-center space-y-4">

                            <div className="flex justify-center">
                                <div className="w-12 h-12 bg-[#FEF8EA] rounded-full flex items-center justify-center">
                                    <Star size={20} color="#FABD2E" fill="#FABD2E" />
                                </div>
                            </div>

                            <div>
                                <h3 className="text-lg font-medium text-[#1F1E1E]">
                                    You haven&apos;t added any review
                                </h3>
                                <p className="text-base text-[#4D4D4D] mb-4">
                                    Add your review for {doctorName || "your doctor"}
                                </p>
                                <Button className="px-10 font-semibold py-3 h-auto">
                                    Add Review
                                    <ChevronRight size={22} strokeWidth={3} className="m-0" />
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </DialogTrigger>

            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Your Review</DialogTitle>
                    <DialogDescription>
                        Share your experience by rating and leaving a review.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Rating</label>
                        <div className="flex gap-1">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <button
                                    key={star}
                                    type="button"
                                    className="p-1 transition-colors"
                                    onClick={() => setRating(star)}
                                    onMouseEnter={() => setHoveredRating(star)}
                                    onMouseLeave={() => setHoveredRating(0)}
                                >
                                    <Star
                                        className={cn(
                                            "h-6 w-6 transition-colors",
                                            star <= (hoveredRating || rating)
                                                ? "fill-yellow-400 text-yellow-400"
                                                : "text-gray-300"
                                        )}
                                    />
                                </button>
                            ))}
                        </div>

                        {rating > 0 && (
                            <p className="text-sm text-muted-foreground">
                                {rating === 1 && "Poor"}
                                {rating === 2 && "Fair"}
                                {rating === 3 && "Good"}
                                {rating === 4 && "Very Good"}
                                {rating === 5 && "Excellent"}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <label htmlFor="review-comment" className="text-sm font-medium">
                            Your Review (Optional)
                        </label>
                        <Textarea
                            id="review-comment"
                            placeholder="Share details about your experience..."
                            value={comment}
                            onChange={(e) => setComment(e.target.value)}
                            className="min-h-[100px]"
                            maxLength={500}
                        />
                        <p className="text-xs text-muted-foreground text-right">
                            {comment.length}/500
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={handleCancel} disabled={isPending}>
                        Cancel
                    </Button>
                    <Button onClick={handleSubmit} disabled={rating === 0 || isPending}>
                        {isPending ? "Submitting..." : "Submit Review"}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}