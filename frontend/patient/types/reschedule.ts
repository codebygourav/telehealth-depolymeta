export interface RescheduleAppointmentPayload {
  appointment_id: string;
  availability_id: string;
  appointment_date: string;
  appointment_time: string;
}

// export interface RescheduleAppointmentData {
//   success: boolean;
//   message: string;
//   path: string;
//   timestamp: string;
//   data?: unknown;
//   appointment_status: string;
// }

export interface RescheduleAppointmentData {
  success: boolean;
  message: string;
  path: string;
  timestamp: string;
  data?: unknown;  // <-- This is 'unknown', so TS can't infer properties
  appointment_status: string;  // <-- This is at the TOP level
}

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