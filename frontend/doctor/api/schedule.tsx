import api from "@/lib/axios";
import { ScheduleResponse } from "@/types/schedule";


export const fetchSchedule = async (): Promise<ScheduleResponse> => {
    const { data } = await api.get('doctor/schedule?filter=month');
    return data;
};