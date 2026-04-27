import { useQuery } from "@tanstack/react-query";
import { getDoctorHome } from "@/api/home";

export const useDoctorHome = () => {
  return useQuery({
    queryKey: ["doctor-home"],
    queryFn: getDoctorHome,
    staleTime: 30 * 1000, // 30 seconds - dashboard data changes frequently
    gcTime: 5 * 60 * 1000, // 5 minutes
  });
};