import { getPatientHome } from "@/api/home";
import { useQuery } from "@tanstack/react-query";

export const PATIENT_HOME_QUERY_KEY = ["patient", "home"] as const;

export function usePatientHome() {
  return useQuery({
    queryKey: PATIENT_HOME_QUERY_KEY,
    queryFn: getPatientHome,
    staleTime: 1000 * 60 * 5, // 5 minutes
  });
}
