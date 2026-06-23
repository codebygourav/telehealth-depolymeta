import axiosInstance from "@/lib/axios";
import type {
  AssignMedicineTemplatePayload,
  GetMedicineTemplatesResponse,
  MedicineTemplate,
} from "@/types/medicine-template";

export const getMedicineTemplates =
  async (): Promise<GetMedicineTemplatesResponse> => {
    const response = await axiosInstance.get<GetMedicineTemplatesResponse>(
      "/doctor/medicine-templates",
      {
        params: {
          active_only: true,
          per_page: 100,
        },
      },
    );

    return response.data;
  };

export const getMedicineTemplate = async (
  id: string,
): Promise<{ success: boolean; data: MedicineTemplate }> => {
  const response = await axiosInstance.get<{
    success: boolean;
    data: MedicineTemplate;
  }>(`/doctor/medicine-templates/${id}`);

  return response.data;
};

export const assignMedicineTemplate = async (
  appointmentId: string,
  payload: AssignMedicineTemplatePayload,
) => {
  const response = await axiosInstance.post(
    `/doctor/${appointmentId}/assign-medicine-template`,
    payload,
  );

  return response.data;
};
