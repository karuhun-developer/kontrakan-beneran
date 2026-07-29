export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type SelectOption = {
    value: number | string;
    label: string;
    monthly_price?: number;
};

export type RoomTypeListItem = {
    id: number;
    name: string;
    capacity: number;
    monthly_price: number;
    rooms_count: number;
    status: string;
    status_label: string;
};

export type FacilityListItem = {
    id: number;
    name: string;
    description: string | null;
    status: string;
    status_label: string;
};

export type RoomListItem = {
    id: number;
    number: string;
    name: string | null;
    floor: number;
    room_type: string | null;
    monthly_price: number;
    status: string;
    status_label: string;
    room_status: string;
    room_status_label: string;
    room_status_color: string;
};

export type PenyewaListItem = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    gender: string | null;
    status: string;
    status_label: string;
    id_card_url: string | null;
};

export type BookingListItem = {
    id: number;
    room: string | null;
    penyewa: string | null;
    start_date: string;
    end_date: string;
    monthly_price: number;
    status: string;
    status_label: string;
    status_color: string;
};

export type BookingLogItem = {
    id: number;
    event: string;
    event_label: string;
    description: string | null;
    user: string | null;
    created_at: string | null;
};

export type BookingDetail = {
    id: number;
    room: string;
    penyewa: string;
    penyewa_phone: string | null;
    start_date: string;
    end_date: string;
    monthly_price: number;
    status: string;
    status_label: string;
    status_color: string;
    notes: string | null;
    checked_in_at: string | null;
    checked_out_at: string | null;
    can_check_in: boolean;
    can_check_out: boolean;
    can_cancel: boolean;
    can_extend: boolean;
    logs: BookingLogItem[];
};

export type CalendarDay = {
    day: number;
    weekday: string;
    is_weekend: boolean;
    is_today: boolean;
};

export type TimelineBar = {
    id: number;
    penyewa: string;
    start_index: number;
    span: number;
    status: string;
    status_label: string;
    status_color: string;
    start_date: string;
    end_date: string;
    continues_before: boolean;
    continues_after: boolean;
};

export type TimelineRoom = {
    id: number;
    number: string;
    name: string | null;
    bars: TimelineBar[];
};

export type TimelineGroup = {
    name: string;
    rooms: TimelineRoom[];
};

export type BookingCalendar = {
    month: string;
    month_label: string;
    days_in_month: number;
    prev_month: string;
    next_month: string;
    days: CalendarDay[];
    groups: TimelineGroup[];
};

export type InvoiceListItem = {
    id: number;
    number: string;
    penyewa: string;
    period_start: string;
    period_end: string;
    due_date: string;
    total: number;
    paid: number;
    outstanding: number;
    status: string;
    status_label: string;
    status_color: string;
};

export type InvoiceStats = {
    outstanding_count: number;
    outstanding_value: number;
    overdue_count: number;
    received_this_month: number;
};

export type InvoiceItemRow = {
    id: number;
    type: string;
    type_label: string;
    description: string;
    quantity: number;
    unit_price: number;
    amount: number;
};

export type PaymentRow = {
    id: number;
    amount: number;
    method: string;
    method_label: string;
    status: string;
    status_label: string;
    status_color: string;
    reference: string | null;
    recorded_by: string | null;
    paid_at: string | null;
    proof_url: string | null;
    proof_thumb_url: string | null;
    can_review: boolean;
};

export type InvoiceDetail = {
    id: number;
    number: string;
    penyewa: string;
    penyewa_phone: string | null;
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
    paid_at: string | null;
    can_pay: boolean;
    can_void: boolean;
    items: InvoiceItemRow[];
    payments: PaymentRow[];
};

export type CashflowListItem = {
    id: number;
    type: string;
    type_label: string;
    type_color: string;
    amount: number;
    description: string;
    category: string | null;
    occurred_on: string;
    is_system: boolean;
};

export type CashflowStats = {
    balance: number;
    income_this_month: number;
    expense_this_month: number;
};

export type CashflowCalendarDay = {
    date: string;
    day: number;
    in_month: boolean;
    is_today: boolean;
    is_weekend: boolean;
    income: number;
    expense: number;
};

export type CashflowCategoryTotal = {
    name: string;
    type: string;
    type_label: string;
    type_color: string;
    total: number;
};

export type CashflowCalendar = {
    month: string;
    month_label: string;
    prev_month: string;
    next_month: string;
    weekdays: string[];
    weeks: CashflowCalendarDay[][];
    summary: { income: number; expense: number; net: number };
    categories: CashflowCategoryTotal[];
};

export type DepositListItem = {
    id: number;
    penyewa: string;
    amount: number;
    balance: number;
    held_at: string;
    settled_at: string | null;
    status: string;
    status_label: string;
    status_color: string;
};

export type DepositStats = {
    held_balance: number;
    active_count: number;
    refunded_this_month: number;
};

export type DepositTransactionRow = {
    id: number;
    type: string;
    type_label: string;
    type_color: string;
    sign: number;
    amount: number;
    reason: string | null;
    occurred_on: string;
    recorded_by: string | null;
};

export type DepositDetail = {
    id: number;
    penyewa: string;
    penyewa_phone: string | null;
    room: string | null;
    amount: number;
    balance: number;
    held_at: string;
    settled_at: string | null;
    status: string;
    status_label: string;
    status_color: string;
    notes: string | null;
    can_transact: boolean;
    transactions: DepositTransactionRow[];
};

export type LaundryListItem = {
    id: number;
    number: string;
    penyewa: string;
    weight: number | null;
    price: number;
    ordered_on: string;
    status: string;
    status_label: string;
    status_color: string;
    payment_source_label: string;
    payment_source_color: string;
};

export type LaundryStats = {
    open_orders: number;
    ready_orders: number;
    billed_this_month: number;
};

export type LaundryDetail = {
    id: number;
    number: string;
    penyewa: string;
    penyewa_phone: string | null;
    weight: number | null;
    price: number;
    ordered_on: string;
    notes: string | null;
    recorded_by: string | null;
    status: string;
    status_label: string;
    status_color: string;
    payment_source: string;
    payment_source_label: string;
    payment_source_color: string;
    invoice_number: string | null;
    next_status_label: string | null;
    can_advance: boolean;
};

export type CashflowCategoryOption = {
    value: number;
    label: string;
    type: string;
};

export type CashflowCategoryListItem = {
    id: number;
    name: string;
    type: string;
    type_label: string;
    type_color: string;
    is_system: boolean;
    status: string;
    status_label: string;
};
