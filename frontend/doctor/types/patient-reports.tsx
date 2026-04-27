export interface ReportFile {
  url: string;
  name: string;
  type: string;
}

export interface PatientReportItem {
  id: string;
  patient_id: string;
  report_name: string;
  report_type: string;
  type_label: string;
  report_date: string;
  report_date_formatted: string;
  uploaded_at: string;
  status: string;
  files: ReportFile | null;
  avatar: string | null;
}

export interface PatientReportPatient {
  id: string;
  patient_id: string;
  name: string;
  avatar: string | null;
  blood_group: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  pincode: string | null;
  area: string | null;
  landmark: string | null;
  city: string | null;
  state: string | null;
  age: number | null;
  gender: string | null;
  total_reports_count: number;
  reports: PatientReportItem[];
}

export interface PaginationMeta {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface GetPatientGroupedReportsResponse {
  success: boolean;
  message: string;
  pagination: PaginationMeta;
  path: string;
  timestamp: string;
  data: PatientReportPatient[];
}

export interface GetAllReportsResponse {
  success: boolean;
  message: string;
  pagination: PaginationMeta;
  path: string;
  timestamp: string;
  data: PatientReportItem[];
}