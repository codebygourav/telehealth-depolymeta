export interface SlotItem {
  id: string;
  date: string;
  day_of_week: string;
  booking_start_time: string;
  start_time: string;
  end_time: string;
  consultation_type: string;
  consultation_type_label: string;
  capacity: number;
  booked_count: number;
  available: boolean;
  consultation_fee: number;
  doctor_room: string | null;
  recurring_start_date: string;
  recurring_end_date: string;
}

export interface SlotGroup {
  date: string;
  slots: SlotItem[];
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data: T;
}