export interface TransactionItem {
  id: string;
  patient_name: string;
  doctor_name: string;
  amount: string;
  currency: string;
  status: string;
  status_label: string;
  transaction_id: string | null;
  order_id: string | null;
  date: string;
  payment_type: string;
  payment_method: string;
  upi_id?: string | null;
  bank_name?: string | null;
}

export interface TransactionsPagination {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

export interface GetTransactionsResponse {
  success: boolean;
  message: string;
  pagination: TransactionsPagination;
  path: string;
  timestamp: string;
  data: TransactionItem[];
}