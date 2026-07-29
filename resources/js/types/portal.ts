export type PortalInvoiceListItem = {
    id: number;
    number: string;
    period_start: string;
    period_end: string;
    due_date: string;
    total: number;
    outstanding: number;
    status: string;
    status_label: string;
    status_color: string;
};

export type PortalInvoiceItem = {
    id: number;
    type_label: string;
    description: string;
    quantity: number;
    unit_price: number;
    amount: number;
};

export type PortalInvoicePayment = {
    id: number;
    amount: number;
    method_label: string;
    status_label: string;
    status_color: string;
    paid_at: string | null;
};

export type PortalInvoiceDetail = {
    id: number;
    number: string;
    room: string | null;
    period_start: string;
    period_end: string;
    due_date: string;
    subtotal: number;
    discount: number;
    fine: number;
    total: number;
    paid: number;
    outstanding: number;
    status: string;
    status_label: string;
    status_color: string;
    notes: string | null;
    can_pay: boolean;
    items: PortalInvoiceItem[];
    payments: PortalInvoicePayment[];
};
