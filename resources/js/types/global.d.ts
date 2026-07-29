import type { Auth } from '@/types/auth';
import type { BranchContext, FlashMessages, TenantSummary } from '@/types/branch';
import type { NotificationFeed } from '@/types/notification';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            tenant: TenantSummary | null;
            branchContext: BranchContext;
            flash: FlashMessages;
            notificationFeed: NotificationFeed;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
