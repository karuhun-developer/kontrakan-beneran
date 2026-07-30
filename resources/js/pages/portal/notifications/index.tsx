import { Head, router, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { Bell, Check } from 'lucide-react';
import { Pagination } from '@/components/master/pagination';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import portalInvoices from '@/routes/portal/invoices';
import portalNotifications from '@/routes/portal/notifications';
import type { Paginated } from '@/types/master';
import type { NotificationItem } from '@/types/notification';

type PageProps = {
    notifications: Paginated<NotificationItem>;
};

export default function PortalNotificationsIndex() {
    const { notifications: feed } = usePage<PageProps>().props;
    const hasUnread = feed.data.some((item) => !item.read_at);

    const markRead = (id: string) => {
        router.post(
            portalNotifications.read(id).url,
            {},
            { preserveScroll: true, preserveState: true },
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
        <>
            <Head title="Notifikasi" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Notifikasi
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Pengingat tagihan jatuh tempo dan pemberitahuan
                            lainnya.
                        </p>
                    </div>
                    {hasUnread && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={markAllRead}
                            data-test="mark-all-read"
                        >
                            <Check className="size-4" />
                            Tandai semua dibaca
                        </Button>
                    )}
                </div>

                <Card>
                    <CardContent className="p-0">
                        {feed.data.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-16 text-center text-muted-foreground">
                                <Bell className="size-8" />
                                <p>Belum ada notifikasi.</p>
                            </div>
                        ) : (
                            <ul className="divide-y">
                                {feed.data.map((item) => (
                                    <li
                                        key={item.id}
                                        className={cn(
                                            'flex items-start gap-3 px-5 py-4',
                                            !item.read_at && 'bg-muted/40',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'mt-1.5 size-2 shrink-0 rounded-full',
                                                item.read_at
                                                    ? 'bg-transparent'
                                                    : 'bg-primary',
                                            )}
                                        />
                                        <div className="flex-1">
                                            <p
                                                className={cn(
                                                    'text-sm',
                                                    !item.read_at &&
                                                        'font-medium',
                                                )}
                                            >
                                                {item.data.message ??
                                                    'Notifikasi'}
                                            </p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    item.created_at,
                                                )}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            {item.data.invoice_id && (
                                                <ModalLink
                                                    href={
                                                        portalInvoices.show(
                                                            item.data
                                                                .invoice_id,
                                                        ).url
                                                    }
                                                    onClick={() =>
                                                        !item.read_at &&
                                                        markRead(item.id)
                                                    }
                                                    className={buttonVariants({
                                                        variant: 'outline',
                                                        size: 'sm',
                                                    })}
                                                >
                                                    Lihat tagihan
                                                </ModalLink>
                                            )}
                                            {!item.read_at && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        markRead(item.id)
                                                    }
                                                    aria-label="Tandai dibaca"
                                                >
                                                    <Check className="size-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Pagination
                    links={feed.links}
                    from={feed.from}
                    to={feed.to}
                    total={feed.total}
                />
            </div>
        </>
    );
}

PortalNotificationsIndex.layout = {
    breadcrumbs: [{ title: 'Notifikasi', href: portalNotifications.index() }],
};
