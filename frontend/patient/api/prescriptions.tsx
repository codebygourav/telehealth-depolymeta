import axiosInstance from "@/lib/axios";
import type { GetPrescriptionsResponse, MedicineDetailsResponse } from "@/types/prescriptions";

export const getPrescriptions = async ({
  patientID,
  filter,
}: {
  patientID: string;
  filter: "current" | "past";
}): Promise<GetPrescriptionsResponse> => {
  const response = await axiosInstance.get<GetPrescriptionsResponse>(
    `/prescriptions/${patientID}`,
    {
      params: { filter },
    },
  );
  return response.data;
};

export const getPrescriptionDetail = async (
  appointmentID: string,
): Promise<MedicineDetailsResponse> => {
  const response = await axiosInstance.get<MedicineDetailsResponse>(
    `/prescriptions/detail/${appointmentID}`,
  );
  return response.data;
};


