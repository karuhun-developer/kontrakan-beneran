import { Head, router, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { Bell, Check } from 'lucide-react';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import invoices from '@/routes/invoices';
import notifications from '@/routes/notifications';
import type { Paginated } from '@/types/master';
import type { NotificationItem } from '@/types/notification';

type PageProps = {
    notifications: Paginated<NotificationItem>;
};

export default function NotificationsIndex() {
    const { notifications: feed } = usePage<PageProps>().props;
    const hasUnread = feed.data.some((item) => !item.read_at);

    const markRead = (id: string) => {
        router.post(
            notifications.read(id).url,
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    const markAllRead = () => {
        router.post(
            notifications.readAll().url,
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <>
            <Head title="Notifikasi" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <PageHeader
                        title="Notifikasi"
                        description="Pengingat tagihan jatuh tempo dan pemberitahuan lainnya."
                    />
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
                                                        invoices.show(
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

NotificationsIndex.layout = {
    breadcrumbs: [{ title: 'Notifikasi', href: notifications.index() }],
};
