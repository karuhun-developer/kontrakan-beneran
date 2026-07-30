import { Head, router, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { CheckCircle2, Clock, Eye, Shirt } from 'lucide-react';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { ColorBadge } from '@/components/master/status-badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCan } from '@/hooks/use-can';
import { formatDate, formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import laundry from '@/routes/laundry';
import type {
    LaundryListItem,
    LaundryStats,
    Paginated,
    SelectOption,
} from '@/types/master';

type PageProps = {
    orders: Paginated<LaundryListItem>;
    stats: LaundryStats;
    filters: { status: string | null };
    statusOptions: SelectOption[];
};

function StatCard({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: typeof Shirt;
    label: string;
    value: string;
    tone?: string;
}) {
    return (
        <Card>
            <CardContent className="flex items-center gap-4 py-5">
                <div
                    className={cn(
                        'flex size-11 items-center justify-center rounded-lg bg-muted text-muted-foreground',
                        tone,
                    )}
                >
                    <Icon className="size-5" />
                </div>
                <div>
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className="text-xl font-semibold">{value}</p>
                </div>
            </CardContent>
        </Card>
    );
}

export default function LaundryIndex() {
    const { orders, stats, filters, statusOptions } =
        usePage<PageProps>().props;
    const canManage = useCan('laundry.manage');

    const applyStatus = (status: string | null) => {
        router.get(laundry.index().url, status ? { status } : {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <>
            <Head title="Laundry" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Laundry"
                    description="Catat order laundry penyewa — potong deposit atau masuk tagihan."
                    action={
                        canManage
                            ? {
                                  label: 'Order Baru',
                                  href: laundry.create(),
                              }
                            : undefined
                    }
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        icon={Clock}
                        label="Sedang Diproses"
                        value={`${stats.open_orders} order`}
                        tone="bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300"
                    />
                    <StatCard
                        icon={CheckCircle2}
                        label="Siap Diambil"
                        value={`${stats.ready_orders} order`}
                        tone="bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                    />
                    <StatCard
                        icon={Shirt}
                        label="Nilai Bulan Ini"
                        value={formatRupiah(stats.billed_this_month)}
                        tone="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
                    />
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant={filters.status ? 'outline' : 'default'}
                        onClick={() => applyStatus(null)}
                    >
                        Semua
                    </Button>
                    {statusOptions.map((option) => (
                        <Button
                            key={option.value}
                            type="button"
                            size="sm"
                            variant={
                                filters.status === option.value
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() => applyStatus(String(option.value))}
                        >
                            {option.label}
                        </Button>
                    ))}
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>No.</TableHead>
                                    <TableHead>Penyewa</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead className="text-right">
                                        Harga
                                    </TableHead>
                                    <TableHead>Pembayaran</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="w-12" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {orders.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada order laundry.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {orders.data.map((order) => (
                                    <TableRow key={order.id}>
                                        <TableCell className="font-mono text-xs">
                                            {order.number}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {order.penyewa}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(order.ordered_on)}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {formatRupiah(order.price)}
                                        </TableCell>
                                        <TableCell>
                                            <ColorBadge
                                                label={
                                                    order.payment_source_label
                                                }
                                                color={
                                                    order.payment_source_color
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <ColorBadge
                                                label={order.status_label}
                                                color={order.status_color}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <ModalLink
                                                href={
                                                    laundry.show(order.id).url
                                                }
                                                className={buttonVariants({
                                                    variant: 'ghost',
                                                    size: 'icon',
                                                })}
                                            >
                                                <Eye className="size-4" />
                                                <span className="sr-only">
                                                    Detail
                                                </span>
                                            </ModalLink>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

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

LaundryIndex.layout = {
    breadcrumbs: [{ title: 'Laundry', href: laundry.index() }],
};
