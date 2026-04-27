import axiosInstance from "@/lib/axios";
import type { ApiResponse, SlotGroup } from "@/types/slots";

export const getDoctorAvailableSlots = async (
  doctorId: string
): Promise<ApiResponse<SlotGroup[]>> => {
  const response = await axiosInstance.get<ApiResponse<SlotGroup[]>>(
    `/doctor/${doctorId}/get-slot-detail`
  );

  return response.data;
};