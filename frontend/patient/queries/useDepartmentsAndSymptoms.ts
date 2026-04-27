import { useQuery } from "@tanstack/react-query";
import { getDepartmentsAndSymptoms } from "@/api/departments";

export const departmentKeys = {
  all: ["departments-and-symptoms"] as const,
};

export const useDepartmentsAndSymptoms = () => {
  return useQuery({
    queryKey: departmentKeys.all,
    queryFn: getDepartmentsAndSymptoms,
    staleTime: 1000 * 60 * 10, // 10 minutes
  });
};