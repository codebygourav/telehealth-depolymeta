import { getMedicineTemplates } from "@/api/medicine-templates";
import { useQuery } from "@tanstack/react-query";

export const useMedicineTemplates = () => {
  return useQuery({
    queryKey: ["medicine-templates"],
    queryFn: getMedicineTemplates,
    staleTime: 60 * 1000,
    gcTime: 5 * 60 * 1000,
  });
};
