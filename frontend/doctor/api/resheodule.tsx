// api file
import api from "@/lib/axios";

export const getDoctorSlots = async (doctorId: string) => {
  console.log( "doctor id" ,doctorId);
  
    const { data } = await api.get(`/doctor/${doctorId}/get-slot-detail`);

  console.log(data);
  
    
    return data;
};