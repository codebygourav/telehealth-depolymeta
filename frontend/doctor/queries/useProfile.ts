// import { useQuery } from "@tanstack/react-query";
// import { getDoctorProfileByGroup } from "@/api/profile";
// import type { DoctorProfileGroup } from "@/types/profile";

// export const doctorProfileKeys = {
//   all: ["doctor-profile"] as const,
//   detail: (doctorID: string, group: DoctorProfileGroup) =>
//     [...doctorProfileKeys.all, doctorID, group] as const,
// };

// export const useDoctorProfile = <TGroup extends DoctorProfileGroup>(
//   doctorID: string,
//   group: TGroup
// ) => {
//   return useQuery({
//     queryKey: doctorProfileKeys.detail(doctorID, group),
//     queryFn: () => getDoctorProfileByGroup({ doctorID, group }),
//     enabled: !!doctorID,
//   });
// };

import { useQuery } from "@tanstack/react-query";
import { getDoctorProfile } from "@/api/profile";

export const doctorProfileKeys = {
  all: ["doctor-profile"] as const,
};

export const useDoctorProfile = () => {
  return useQuery({
    queryKey: doctorProfileKeys.all,
    queryFn: getDoctorProfile,
    staleTime: 5 * 60 * 1000, // 5 minutes - profile changes rarely
    gcTime: 10 * 60 * 1000, // 10 minutes
  });
};