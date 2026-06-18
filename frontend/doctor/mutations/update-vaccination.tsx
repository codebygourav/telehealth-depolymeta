import { updatePatientVaccination } from '@/api/patient-vaccinations';
import { useMutation } from '@tanstack/react-query';

export const useUpdatePatientVaccination = () => {
    return useMutation({
        mutationFn: ({ id, body }: { id: string; body: Record<string, unknown> }) =>
            updatePatientVaccination(id, body),
    });
};
