export type BranchSummary = {
    id: number;
    name: string;
};

export type TenantSummary = {
    id: number;
    name: string;
};

export type BranchContext = {
    current: BranchSummary | null;
    available: BranchSummary[];
    canViewAll: boolean;
};

export type FlashMessages = {
    success: string | null;
    error: string | null;
    warning: string | null;
};
