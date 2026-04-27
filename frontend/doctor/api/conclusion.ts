import api from "@/lib/axios";

export interface SubmitConclusionPayload {
  appointmentId: string;
  instructions_by_doctor: string;
  next_visit_date: string;
  type?: string;
  files?: File[];
}

export interface SubmitConclusionResponse {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: {
    appointment_id: string;
    instructions_by_doctor: string;
    next_visit_date: string;
  };
}

export const submitConclusion = async ({
  appointmentId,
  instructions_by_doctor,
  next_visit_date,
  type,
  files,
}: SubmitConclusionPayload): Promise<SubmitConclusionResponse> => {
  const formData = new FormData();

  formData.append("instructions_by_doctor", instructions_by_doctor);
  formData.append("next_visit_date", next_visit_date);

  if (type) {
    formData.append("type", type);
  }

  if (files && files.length > 0) {
    files.forEach((file, index) => {
      formData.append(`files[${index}]`, file);
    });
  }

  const { data } = await api.post(
    `/appointments/doctor-instructions/${appointmentId}`,
    formData,
    {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    }
  );

  return data;
};