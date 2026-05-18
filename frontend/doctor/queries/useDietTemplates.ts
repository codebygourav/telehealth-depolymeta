import { getDietTemplates } from "@/api/diet-template";
import { useQuery } from "@tanstack/react-query";

export const useDietTemplates = () => {
  return useQuery({
    queryKey: ["diet-templates"],
    queryFn: getDietTemplates,
  });
};
