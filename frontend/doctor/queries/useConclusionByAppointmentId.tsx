import { useQuery } from "@tanstack/react-query";
import api from "@/lib/axios";

interface ConclusionData {
  instructions_by_doctor?: string;
  next_visit_date?: string;
  type?: string;
  conclusion_report_files?: Array<{
    id: string;
    name: string;
    url: string;
    type: string;
  }>;
}

interface ConclusionResponse {
  data: ConclusionData;
}

export const useConclusionByAppointmentId = (appointmentId: string) => {
  return useQuery({
    queryKey: ["conclusion", appointmentId],
    queryFn: async (): Promise<ConclusionResponse> => {
      const { data } = await api.get(`/appointments/doctor-instructions/${appointmentId}`);
      return data;
    },
    enabled: !!appointmentId,
  });
};
