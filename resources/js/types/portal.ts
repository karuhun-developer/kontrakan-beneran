export type PortalState = {
    must_change_password: boolean;
};

export type PortalDepositListItem = {
    id: number;
    amount: number;
    balance: number;
    status: string;
    status_label: string;
    status_color: string;
    held_at: string | null;
};

export type PortalDepositTransaction = {
    id: number;
    type_label: string;
    type_color: string;
    sign: number;
    amount: number;
    reason: string | null;
    occurred_on: string | null;
};

export type PortalDepositDetail = {
    id: number;
    amount: number;
    balance: number;
    status: string;
    status_label: string;
    status_color: string;
    held_at: string | null;
    settled_at: string | null;
    notes: string | null;
    transactions: PortalDepositTransaction[];
};

export type PortalLaundryListItem = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    status_color: string;
    weight: string | null;
    price: number;
    payment_source_label: string;
    payment_source_color: string;
    ordered_on: string | null;
};

export type PortalLaundryDetail = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    status_color: string;
    weight: string | null;
    price: number;
    payment_source_label: string;
    payment_source_color: string;
    invoice_number: string | null;
    ordered_on: string | null;
    notes: string | null;
};

export type PortalStayListItem = {
    id: number;
    room: string | null;
    start_date: string;
    end_date: string;
    monthly_price: number;
    status: string;
    status_label: string;
    status_color: string;
};

export type PortalStayLog = {
    id: number;
    event_label: string;
    description: string | null;
    occurred_on: string | null;
};

export type PortalStayDetail = {
    id: number;
    room: string | null;
    start_date: string;
    end_date: string;
    monthly_price: number;
    status: string;
    status_label: string;
    status_color: string;
    checked_in_at: string | null;
    checked_out_at: string | null;
    notes: string | null;
    logs: PortalStayLog[];
};

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
