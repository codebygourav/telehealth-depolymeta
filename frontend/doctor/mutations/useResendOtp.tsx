import { useMutation } from "@tanstack/react-query";
import api from "@/lib/axios";

export interface ResendOtpPayload {
  email: string;
  context: "forgot_password" | string;
}

export interface ResendOtpResponse {
  success: boolean;
  message: string;
}

export function useResendOtp() {
  return useMutation<ResendOtpResponse, any, ResendOtpPayload>({
    mutationFn: async (payload) => {
      const { data } = await api.post("/auth/resend-otp", payload);
      return data;
    },
  });
}
