import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import portalInvoices from '@/routes/portal/invoices';
import portalNotifications from '@/routes/portal/notifications';
import type { NotificationItem } from '@/types/notification';

export function PortalNotificationBell() {
    const { notificationFeed: feed } = usePage().props;
    const { unread_count: unreadCount, recent } = feed;

    const openNotification = (item: NotificationItem) => {
        const invoiceId = item.data.invoice_id;

        router.post(
            portalNotifications.read(item.id).url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    if (invoiceId) {
                        router.visit(portalInvoices.show(invoiceId).url, {
                            preserveScroll: true,
                        });
                    }
                },
            },
        );
    };

    const markAllRead = () => {
        router.post(
            portalNotifications.readAll().url,
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className="relative inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    aria-label="Notifikasi"
                    data-test="notification-bell"
                >
                    <Bell className="size-5" />
                    {unreadCount > 0 && (
                        <span
                            className="absolute -top-0.5 -right-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-semibold text-white"
                            data-test="notification-badge"
                        >
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <div className="flex items-center justify-between px-2 py-1.5">
                    <DropdownMenuLabel className="p-0">
                        Notifikasi
                    </DropdownMenuLabel>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={markAllRead}
                            className="text-xs font-medium text-primary hover:underline"
                            data-test="notification-mark-all"
                        >
                            Tandai semua
                        </button>
                    )}
                </div>
                <DropdownMenuSeparator />
                {recent.length === 0 ? (
                    <p className="px-2 py-6 text-center text-sm text-muted-foreground">
                        Belum ada notifikasi.
                    </p>
                ) : (
                    recent.map((item) => (
                        <DropdownMenuItem
                            key={item.id}
                            onSelect={() => openNotification(item)}
                            className="flex flex-col items-start gap-1 whitespace-normal"
                        >
                            <div className="flex w-full items-start gap-2">
                                {!item.read_at && (
                                    <span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" />
                                )}
                                <span
                                    className={cn(
                                        'flex-1 text-sm',
                                        item.read_at
                                            ? 'text-muted-foreground'
                                            : 'font-medium',
                                    )}
                                >
                                    {item.data.message ?? 'Notifikasi'}
                                </span>
                            </div>
                            <span className="pl-4 text-xs text-muted-foreground">
                                {formatDateTime(item.created_at)}
                            </span>
                        </DropdownMenuItem>
                    ))
                )}
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    onSelect={() =>
                        router.visit(portalNotifications.index().url)
                    }
                    className="justify-center text-sm font-medium text-primary"
                    data-test="notification-see-all"
                >
                    Lihat semua
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
