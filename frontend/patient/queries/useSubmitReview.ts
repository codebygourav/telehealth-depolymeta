import { useMutation, useQueryClient } from "@tanstack/react-query";
import { submitReview } from "@/api/review";

export const useSubmitReview = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: submitReview,

    onSuccess: () => {
      // 🔥 Refetch reviews automatically
      queryClient.invalidateQueries({
        queryKey: ["my-reviews"],
      });
    },
  });
};