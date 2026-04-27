export interface Report {
    id: string;
    title: string;
    date: string;
    type: string;
    fileName: string;
    fileUrl?: string;
    file?: File;
}

export interface MedicalRecord {
    id: string;
    title: string;
    date: string;
    doctor: string | { id: string; name: string };
    type: string;
    status: string;
    fileUrl?: string;
}

export interface MedicalReportDoctor {
    id: string;
    name: string;
    first_name: string;
    last_name: string;
    appoinment_id?: string;
    appoinment_status?: string;
    appointment_time_status?: 'upcoming' | 'past';
}

export interface MedicalReport {
    id: string;
    report_name: string;
    report_type: string;
    type_label: string;
    report_date: string;
    report_date_formatted: string;
    file_url: string;
    file_name: string;
    status: string;
    doctor: MedicalReportDoctor;
}

export interface Pagination {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

export interface MedicalReportsResponse {
    success: boolean;
    data: MedicalReport[];
    pagination: Pagination;
}