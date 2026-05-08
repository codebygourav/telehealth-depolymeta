export interface LeaveItem {
    id: string;
    slug: string;
    type: string;
    type_label: string;
    start_date: string;
    start_date_formatted: string;
    end_date: string;
    end_date_formatted: string;
    duration: number;
    duration_text: string;
    reason: string;
    status: string;
    status_label: string;
    status_comment: string | null;
    applied_date: string;
    applied_date_formatted: string;
    created_at: string;
    updated_at: string;
}

export interface LeavesPagination {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

export interface GetLeavesResponse {
    success: boolean;
    message: string;
    pagination: LeavesPagination;
    path: string;
    timestamp: string;
    data: LeaveItem[];
}   