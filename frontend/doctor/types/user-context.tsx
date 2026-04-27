export type UserRole = "patient" | "doctor";

interface Address {
    address: string | null;
    area: string | null;
    city: string | null;
    landmark: string | null;
    pincode: string | null;
    state: string | null;
}

export interface User {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
    role: UserRole;
    avatar?: string;
    patient_id?: string;
    doctor_id?: string;
    gender: string;
    date_of_birth: string;
    phone: string;
    address: Address;
    status: string;
}
