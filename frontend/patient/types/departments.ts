export interface DepartmentInfo {
  name: string;
  icon: string;
  stamp: string | null;
}

export interface SymptomInfo {
  name: string;
  icon: string;
}

export interface DepartmentSymptomItem {
  id: string;
  department: DepartmentInfo;
  symptoms: SymptomInfo[];
}

export interface DepartmentsAndSymptomsResponse {
  success: boolean;
  message: string;
  global_stamp: string | null;
  path: string;
  timestamp: string;
  data: DepartmentSymptomItem[];
}

export interface SpecialtyOption {
  value: string;
  label: string;
}