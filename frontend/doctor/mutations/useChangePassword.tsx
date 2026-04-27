import { useMutation } from "@tanstack/react-query";
import api from "@/lib/axios";

export interface ChangePasswordPayload {
    current_password?: string;
    new_password?: string;
    new_password_confirmation?: string;
}

export interface ChangePasswordResponse {
    success: boolean;
    message: string;
}

export function useChangePassword() {
    return useMutation<ChangePasswordResponse, any, ChangePasswordPayload>({
        mutationFn: async (payload) => {
            const { data } = await api.post("/auth/change-password", payload);
            return data;
        },
    });
}
