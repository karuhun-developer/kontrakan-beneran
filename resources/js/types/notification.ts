export type NotificationData = {
    invoice_id?: number;
    invoice_number?: string;
    penyewa?: string;
    due_date?: string;
    days_before?: number;
    outstanding?: number;
    message?: string;
    [key: string]: unknown;
};

export type NotificationItem = {
    id: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string | null;
};

export type NotificationFeed = {
    unread_count: number;
    recent: NotificationItem[];
};
