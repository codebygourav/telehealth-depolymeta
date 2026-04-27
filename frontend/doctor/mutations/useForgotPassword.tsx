import api from "@/lib/axios";
import { useMutation } from "@tanstack/react-query";

// Types for Send OTP
interface SendOtpPayload {
  email: string;
}

interface SendOtpResponse {
  message: string;
}

// Types for Verify OTP
interface VerifyOtpPayload {
  email: string;
  otp: string;
}

interface VerifyOtpResponse {
  success: boolean;
  message: string;
  data: {
    email: string;
    reset_token: string;
  };
}

// Types for Reset Password
interface ResetPasswordPayload {
  email: string;
  reset_token: string;
  password: string;
  password_confirmation: string;
}

interface ResetPasswordResponse {
  message: string;
}

/**
 * Hook for sending OTP to email for password reset
 */
export function useSendOtp() {
  return useMutation<SendOtpResponse, any, SendOtpPayload>({
    mutationFn: async (payload) => {
      const { data } = await api.post("/auth/forgot-password/send-otp", payload);
      return data;
    },
  });
}

/**
 * Hook for verifying the OTP sent to email
 */
export function useVerifyOtp() {
  return useMutation<VerifyOtpResponse, any, VerifyOtpPayload>({
    mutationFn: async (payload) => {
      const { data } = await api.post("/auth/forgot-password/verify-otp", payload);
      return data;
    },
  });
}

/**
 * Hook for resetting the password
 */
export function useResetPassword() {
  return useMutation<ResetPasswordResponse, any, ResetPasswordPayload>({
    mutationFn: async (payload) => {
      const { data } = await api.post("/auth/forgot-password/reset", payload);
      return data;
    },
  });
}
