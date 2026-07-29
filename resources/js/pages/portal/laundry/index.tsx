import { Head, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { Shirt } from 'lucide-react';
import { Pagination } from '@/components/master/pagination';
import { ColorBadge } from '@/components/master/status-badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate, formatRupiah } from '@/lib/format';
import portalLaundry from '@/routes/portal/laundry';
import type { Paginated } from '@/types/master';
import type { PortalLaundryListItem } from '@/types/portal';

type PageProps = {
    orders: Paginated<PortalLaundryListItem>;
    summary: { open_count: number };
};

export default function PortalLaundryIndex() {
    const { orders, summary } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Laundry" />

            <div className="flex flex-col gap-6">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Laundry
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {summary.open_count > 0
                            ? `${summary.open_count} order sedang berjalan.`
                            : 'Riwayat order laundry kamu.'}
                    </p>
                </div>

                {orders.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-muted-foreground">
                            <Shirt className="size-8" />
                            <p>Belum ada order laundry.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-3">
                        {orders.data.map((order) => (
                            <Card key={order.id}>
                                <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-mono text-sm">
                                                {order.number}
                                            </span>
                                            <ColorBadge
                                                label={order.status_label}
                                                color={order.status_color}
                                            />
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                            {order.weight && (
                                                <span>{order.weight} kg</span>
                                            )}
                                            <ColorBadge
                                                label={
                                                    order.payment_source_label
                                                }
                                                color={
                                                    order.payment_source_color
                                                }
                                            />
                                            {order.ordered_on && (
                                                <span>
                                                    {formatDate(
                                                        order.ordered_on,
                                                    )}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-4">
                                        <p className="font-semibold">
                                            {formatRupiah(order.price)}
                                        </p>
                                        <ModalLink
                                            href={
                                                portalLaundry.show(order.id).url
                                            }
                                            className={buttonVariants({
                                                variant: 'outline',
                                                size: 'sm',
                                            })}
                                        >
                                            Detail
                                        </ModalLink>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Pagination
                    links={orders.links}
                    from={orders.from}
                    to={orders.to}
                    total={orders.total}
                />
            </div>
        </>
    );
}

PortalLaundryIndex.layout = {
    breadcrumbs: [{ title: 'Laundry', href: portalLaundry.index() }],
};
