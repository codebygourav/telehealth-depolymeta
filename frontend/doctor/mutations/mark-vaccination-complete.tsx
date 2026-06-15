import { completePatientVaccination } from '@/api/patient-vaccinations';
import { useMutation } from '@tanstack/react-query';

export const useMarkVaccinationComplete = () => {
    return useMutation({
        mutationFn: ({ id, body }: { id: string; body?: Record<string, unknown> }) =>
            completePatientVaccination(id, body ?? {}),
    });
};
