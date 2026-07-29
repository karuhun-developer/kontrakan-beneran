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
