import { assignVaccinationTemplate } from "@/api/patient-vaccinations";
import { useMutation } from "@tanstack/react-query";

export const useAssignVaccinationTemplate = () => {
    return useMutation({
        mutationFn: ({ patientId, templateId, firstDoseDate }: { patientId: string; templateId: string; firstDoseDate?: string }) =>
            assignVaccinationTemplate(patientId, templateId, firstDoseDate),
    });
};
