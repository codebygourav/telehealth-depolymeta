
export interface ScheduleSlot {
    id: string | null;
    appointment_id: string;
    date: string;
    day_name: string;
    start_time: string;
    end_time: string;
    time_range: string;
    consultation_type: string;
    consultation_type_label: string;
    capacity: number;
    slot_capacity: number;
    booked_count: number;
    available_slots: number;
    is_recurring: boolean;
    doctor_room: string | null;
    is_available: boolean;
}

export interface ScheduleDay {
    date: string;
    day_name: string;
    day_short: string;
    slots: OPDSlot[];
}

export interface ScheduleData {
    start_date: string;
    end_date: string;
    days: ScheduleDay[];
}

export interface ScheduleResponse {
    success: boolean;
    message: string;
    filter: string;
    path: string;
    timestamp: string;
    data: ScheduleData;
}

export interface PatientAppointment {
    id: string,
    appointment_id?: string
    name: string
    age?: number
    phone?: string
    email?: string
    appointmentTime: string
    reason?: string
    bookedOn?: string
    status: "confirmed" | "pending" | "cancelled" | "completed" | "Scheduled"
    status_label?: string
    doctorName?: string
    doctorAvatar?: string
    patient_name?: string
    patient_avatar?: string | null
    consultation_type?: string
    start_time?: string
    end_time?: string
    appointment_time_formatted?: string
    patient: {
        id?: string;
        name?: string;
        patient_name?: string;
        avatar?: string | null;
        slug?: string;
        user_id?: string;
    };
}

export interface OPDSlot {
    id: string | null
    startTime: string
    appointment_id?: string | null
    date: string
    day_name: string
    start_time: string
    end_time: string
    time_range: string
    consultation_type: "video" | "in-person"
    consultation_type_label: string
    capacity: number
    slot_capacity: number
    booked_count: number
    available_slots: number
    is_recurring?: boolean
    doctor_room?: string | null
    is_available?: boolean
    appointments: PatientAppointment[]
    // For UI compatibility
    timeSlot?: string
    type?: "Telehealth" | "In-Person"
    platform?: string
    location?: string
    totalCapacity?: number
    doctorName?: string
    doctorSpecialization?: string
    doctorAvatar?: string
}