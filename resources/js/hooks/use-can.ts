import { usePage } from '@inertiajs/react';
import type { Auth } from '@/types';

/**
 * Checks whether the authenticated user holds a given permission.
 * Super admins implicitly pass every check (mirrors the backend Gate::before).
 */
export function useCan(permission: string): boolean {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;

    if (!user) {
        return false;
    }

    if (user.role === 'super-admin') {
        return true;
    }

    return user.permissions?.includes(permission) ?? false;
}

/**
 * Convenience checks for the master-data domain.
 */
export function useCanManageMaster(): boolean {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;

    if (!user) {
        return false;
    }

    if (user.role === 'super-admin') {
        return true;
    }

    return (user.permissions ?? []).some((permission) =>
        permission.endsWith('.manage'),
    );
}
