import { getPrescriptionDetail } from '@/api/prescriptions';
import { useQuery } from '@tanstack/react-query';

export const PRESCRIPTION_DETAIL_QUERY_KEY = ['prescription_detail'] as const;

export function usePrescriptionDetail(appointmentID: string | undefined) {
    return useQuery({
        queryKey: [...PRESCRIPTION_DETAIL_QUERY_KEY, appointmentID],
        queryFn: () => getPrescriptionDetail(appointmentID!),
        enabled: !!appointmentID,
        staleTime: 1000 * 60 * 5, // 5 minutes
    });
}
