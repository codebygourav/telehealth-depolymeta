export interface BookAppointmentPayload {
  doctor_id: string;
  availability_id: string;
  appointment_date: string;
  appointment_time: string;
  consultation_type: string;
  opd_type: string;
  notes?: string;
}

export interface BookAppointmentData {
  appointment: {
    id: string;
    slug: string;
    date: string;
    time: string;
    status: string;
  };
  payment: {
    status: string;
    order_id: string;
    payment_id: string | null;
    amount: string;
    amount_paise: number;
    razorpay_key_id: string;
  };
  // Optional fields for UI compatibility
  consultation_type?: string;
  opd_type?: string;
}

export interface BookAppointmentResponse {
  success: boolean;
  message: string;
  path?: string;
  timestamp?: string;
  data?: BookAppointmentData;
}