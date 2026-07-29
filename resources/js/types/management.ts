export type TenantListItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    status: string;
    status_label: string;
    branches_count: number;
    users_count: number;
};

export type BranchListItem = {
    id: number;
    name: string;
    address: string | null;
    phone: string | null;
    bank_name: string | null;
    status: string;
    status_label: string;
    rooms_count: number;
    users_count: number;
};

export type ManagedUserListItem = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    role_label: string | null;
    branches: string[];
};

export type ManagementSelectOption = {
    value: string;
    label: string;
};

export type BranchSelectOption = {
    value: number;
    label: string;
};
