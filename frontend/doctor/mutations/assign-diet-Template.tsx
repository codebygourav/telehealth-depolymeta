import { assignDietTemplate } from "@/api/diet-template";
import { useMutation } from "@tanstack/react-query";


export const useAssignDietTemplate = () => {
    return useMutation({
        mutationFn: assignDietTemplate,
    });
};