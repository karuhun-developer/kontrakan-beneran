import { usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import AppLayout from '@/layouts/app-layout';
import PortalLayout from '@/layouts/portal-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

/**
 * Picks the outer shell for settings pages by role: penyewa keep the portal
 * header nav, staff get the admin sidebar. The global `layout(name)` resolver
 * cannot see auth props, so the branch happens here at render time.
 */
export default function SettingsShell({
    children,
    breadcrumbs = [],
}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { auth } = usePage().props;
    const Shell = auth.user?.is_staff ? AppLayout : PortalLayout;

    return (
        <Shell breadcrumbs={breadcrumbs}>
            <SettingsLayout>{children}</SettingsLayout>
        </Shell>
    );
}
