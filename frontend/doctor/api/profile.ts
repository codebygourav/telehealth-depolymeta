// import axiosInstance from "@/lib/axios";
// import type {
//   ApiResponse,
//   DoctorProfileGroup,
//   DoctorProfileGroupMap,
// } from "@/types/profile";

// interface GetDoctorProfileByGroupParams<T extends DoctorProfileGroup> {
//   doctorID: string;
//   group: T;
// }


// export const getDoctorProfileByGroup = async <T extends DoctorProfileGroup>({
//   doctorID,
//   group,
// }: GetDoctorProfileByGroupParams<T>): Promise<ApiResponse<DoctorProfileGroupMap[T]>> => {
//   const response = await axiosInstance.get<ApiResponse<DoctorProfileGroupMap[T]>>(
//     `/doctor/${doctorID}`,
//     {
//       params: { group },
//     }
//   );

//   return response.data;
// };


import axiosInstance from "@/lib/axios";
import type { GetDoctorProfileResponse } from "@/types/profile";

export const getDoctorProfile = async (): Promise<GetDoctorProfileResponse> => {
  const response = await axiosInstance.get<GetDoctorProfileResponse>(
    "/doctor/get-profile"
  );

  return response.data;
};