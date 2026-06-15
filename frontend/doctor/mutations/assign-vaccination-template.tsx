import { assignVaccinationTemplate } from "@/api/patient-vaccinations";
import { useMutation } from "@tanstack/react-query";

export const useAssignVaccinationTemplate = () => {
    return useMutation({
        mutationFn: ({ patientId, templateId }: { patientId: string; templateId: string }) =>
            assignVaccinationTemplate(patientId, templateId),
    });
};
