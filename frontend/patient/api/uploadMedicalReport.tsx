import axiosInstance from "@/lib/axios";

export interface UploadMedicalReportParams {
  patientId: string;
  name: string;
  type: string;
  file: File;
}

export const uploadMedicalReport = async ({
  patientId,
  name,
  type,
  file,
}: UploadMedicalReportParams): Promise<any> => {
  const formData = new FormData();
  formData.append("name", name);
  formData.append("type", type);
  formData.append("file", file);

  const response = await axiosInstance.post(
    `/patient/${patientId}/medical-reports`,
    formData,
    {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    }
  );

  return response.data;
};
