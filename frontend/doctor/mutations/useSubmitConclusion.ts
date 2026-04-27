"use client";

import { useMutation, useQueryClient } from "@tanstack/react-query";
import {
  submitConclusion,
  SubmitConclusionPayload,
  SubmitConclusionResponse,
} from "@/api/conclusion";

export const useSubmitConclusion = () => {
  const queryClient = useQueryClient();

  return useMutation<SubmitConclusionResponse, Error, SubmitConclusionPayload>({
    mutationFn: submitConclusion,
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({
        queryKey: ["prescription", variables.appointmentId],
      });

      queryClient.invalidateQueries({
        queryKey: ["appointment", variables.appointmentId],
      });
    },
  });
};