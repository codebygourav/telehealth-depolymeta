import { getPrescriptions } from "@/api/prescriptions";
import { useQuery } from "@tanstack/react-query";

export const PRESCRIPTIONS_QUERY_KEY = ["prescriptions"] as const;

export function usePrescriptions({
  patientID,
  filter,
}: {
  patientID: string | undefined;
  filter: "current" | "past";
}) {
  return useQuery({
    queryKey: [...PRESCRIPTIONS_QUERY_KEY, patientID, filter],
    queryFn: () => getPrescriptions({ patientID: patientID!, filter }),
    enabled: !!patientID,
    staleTime: 1000 * 60 * 5, // 5 minutes
  });
}
