import { usePage } from '@inertiajs/react';
import type { Auth } from '@/types';

/**
 * Checks whether the authenticated user holds a given permission.
 * Super admins implicitly pass every check (mirrors the backend Gate::before).
 *
 * Called with a permission it returns a boolean; called with no argument it
 * returns a reusable checker — use the latter to gate several items without
 * calling the hook in a loop.
 */
export function useCan(): (permission: string) => boolean;
export function useCan(permission: string): boolean;
export function useCan(
    permission?: string,
): boolean | ((permission: string) => boolean) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const user = auth.user;

    const check = (name: string): boolean => {
        if (!user) {
            return false;
        }

        if (user.role === 'super-admin') {
            return true;
        }

        return user.permissions?.includes(name) ?? false;
    };

    return permission === undefined ? check : check(permission);
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
