import { assignDietTemplate } from "@/api/diet-template";
import {
  AssignDietTemplatePayload,
  AssignDietTemplateResponse,
} from "@/types/diet-template";
import { useMutation } from "@tanstack/react-query";
import { AxiosError } from "axios";

export const useAssignDietTemplate = () => {
  return useMutation<
    AssignDietTemplateResponse,
    AxiosError<{ message?: string }>,
    AssignDietTemplatePayload
  >({
    mutationFn: assignDietTemplate,
  });
};
