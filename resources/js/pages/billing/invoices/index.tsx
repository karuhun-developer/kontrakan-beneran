import { Head, router, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { AlertTriangle, Eye, ReceiptText, Wallet } from 'lucide-react';
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
import { formatDate, formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import invoices from '@/routes/invoices';
import type {
    InvoiceListItem,
    InvoiceStats,
    Paginated,
    SelectOption,
} from '@/types/master';

type PageProps = {
    invoices: Paginated<InvoiceListItem>;
    stats: InvoiceStats;
    filters: { status: string | null };
    statusOptions: SelectOption[];
};

function StatCard({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: typeof Wallet;
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

export default function InvoicesIndex() {
    const {
        invoices: data,
        stats,
        filters,
        statusOptions,
    } = usePage<PageProps>().props;

    const applyStatus = (status: string | null) => {
        router.get(invoices.index().url, status ? { status } : {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <>
            <Head title="Tagihan" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Tagihan"
                    description="Kelola tagihan sewa, catat pembayaran, dan pantau tunggakan."
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        icon={Wallet}
                        label="Nilai Tertunggak"
                        value={formatRupiah(stats.outstanding_value)}
                        tone="bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300"
                    />
                    <StatCard
                        icon={AlertTriangle}
                        label="Jatuh Tempo"
                        value={`${stats.overdue_count} tagihan`}
                        tone="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300"
                    />
                    <StatCard
                        icon={ReceiptText}
                        label="Diterima Bulan Ini"
                        value={formatRupiah(stats.received_this_month)}
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
                                    <TableHead>Nomor</TableHead>
                                    <TableHead>Penyewa</TableHead>
                                    <TableHead>Periode</TableHead>
                                    <TableHead>Jatuh Tempo</TableHead>
                                    <TableHead className="text-right">
                                        Total
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Sisa
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="w-12" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada tagihan.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="font-medium">
                                            {invoice.number}
                                        </TableCell>
                                        <TableCell>{invoice.penyewa}</TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(invoice.period_start)} –{' '}
                                            {formatDate(invoice.period_end)}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(invoice.due_date)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatRupiah(invoice.total)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatRupiah(invoice.outstanding)}
                                        </TableCell>
                                        <TableCell>
                                            <ColorBadge
                                                label={invoice.status_label}
                                                color={invoice.status_color}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <ModalLink
                                                href={
                                                    invoices.show(invoice.id)
                                                        .url
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
                    links={data.links}
                    from={data.from}
                    to={data.to}
                    total={data.total}
                />
            </div>
        </>
    );
}

InvoicesIndex.layout = {
    breadcrumbs: [{ title: 'Tagihan', href: invoices.index() }],
};
