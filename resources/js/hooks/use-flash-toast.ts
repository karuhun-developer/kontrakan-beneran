import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

/**
 * Surfaces Laravel flash messages (`flash.success` / `flash.error`) shared via
 * Inertia as sonner toasts.
 */
export function useFlashToast(): void {
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash?.success, flash?.error]);
}
