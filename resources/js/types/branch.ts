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
};

export type FlashMessages = {
    success: string | null;
    error: string | null;
};
