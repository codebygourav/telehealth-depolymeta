import api from "@/lib/axios";
import { useMutation } from "@tanstack/react-query";

// Types for Register Step 1
export interface SendEmailPayload {
  email: string;
}

export interface SendEmailResponse {
  success: boolean;
  message: string;
  data?: {
    email: string;
  };
  errors?: {
    email?: string;
    status?: string;
    message?: string;
  };
}

// Types for Verify OTP Step 2
export interface VerifyOtpPayload {
  email: string;
  otp: string;
  context?: string;
}

export interface VerifyOtpResponse {
  success: boolean;
  message: string;
  data?: any;
  errors?: {
    message?: string;
  };
}

// Types for Complete Profile Step 3
export interface CompleteProfilePayload {
  email: string;
  password?: string;
  first_name: string;
  last_name: string;
  gender: string;
  date_of_birth: string;
  mobile_no: string;
  referralCode?: string;
  expo_push_token?: string;
  device_type?: string;
  device_name?: string;
  app_version?: string;
  is_underPrimary?: boolean;
}

export interface CompleteProfileResponse {
  success: boolean;
  message: string;
  data: {
    user: any;
    patient: any;
    token: string;
  };
  errors?: {
    message?: string;
  };
}

// Individual hooks for each registration step
export function useRegister() {
  return useMutation<SendEmailResponse, Error, SendEmailPayload>({
    mutationFn: async (payload) => {
      const { data } = await api.post("/auth/register", payload);
      return data;
    },
  });
}

export function useVerifyEmail() {
  return useMutation<VerifyOtpResponse, Error, VerifyOtpPayload>({
    mutationFn: async (payload) => {
      const { data } = await api.post("/auth/verify-email", payload);
      return data;
    },
  });
}

export function useCompleteProfile() {
  return useMutation<CompleteProfileResponse, Error, CompleteProfilePayload>({
    mutationFn: async (payload) => {
      const { data } = await api.post("/auth/complete-profile", payload);
      return data;
    },
  });
}
