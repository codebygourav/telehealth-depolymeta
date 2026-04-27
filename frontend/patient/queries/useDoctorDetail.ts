import { useQuery } from "@tanstack/react-query";
import { getDoctorDetail } from "@/api/doctorDetails";
import type { DoctorDetailResponse } from "@/types/doctor-details";

export const doctorDetailKeys = {
  all: ["doctor-detail"] as const,
  detail: (userId: string) => [...doctorDetailKeys.all, userId] as const,
};

export const useDoctorDetail = (userId: string) => {
  return useQuery<DoctorDetailResponse>({
    queryKey: doctorDetailKeys.detail(userId),
    queryFn: () => getDoctorDetail({ userId }),
    enabled: !!userId,
  });
};