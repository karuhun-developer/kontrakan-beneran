import { Head, router, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { Eye, PiggyBank, RotateCcw, Users } from 'lucide-react';
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
import { cn } from '@/lib/utils';
import { formatDate, formatRupiah } from '@/lib/format';
import deposits from '@/routes/deposits';
import type {
    DepositListItem,
    DepositStats,
    Paginated,
    SelectOption,
} from '@/types/master';

type PageProps = {
    deposits: Paginated<DepositListItem>;
    stats: DepositStats;
    filters: { status: string | null };
    statusOptions: SelectOption[];
};

function StatCard({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: typeof PiggyBank;
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

export default function DepositsIndex() {
    const {
        deposits: data,
        stats,
        filters,
        statusOptions,
    } = usePage<PageProps>().props;
    const canManage = useCan('deposit.manage');

    const applyStatus = (status: string | null) => {
        router.get(deposits.index().url, status ? { status } : {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <>
            <Head title="Deposit" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Deposit"
                    description="Kelola saldo deposit penyewa dan catat potongan atau pengembalian."
                    action={
                        canManage
                            ? {
                                  label: 'Buka Deposit',
                                  href: deposits.create(),
                              }
                            : undefined
                    }
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        icon={PiggyBank}
                        label="Total Ditahan"
                        value={formatRupiah(stats.held_balance)}
                        tone="bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                    />
                    <StatCard
                        icon={Users}
                        label="Deposit Aktif"
                        value={`${stats.active_count} penyewa`}
                        tone="bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300"
                    />
                    <StatCard
                        icon={RotateCcw}
                        label="Dikembalikan Bulan Ini"
                        value={formatRupiah(stats.refunded_this_month)}
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
                                    <TableHead>Penyewa</TableHead>
                                    <TableHead>Ditahan Sejak</TableHead>
                                    <TableHead className="text-right">
                                        Nominal
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Saldo
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="w-12" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada deposit.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((deposit) => (
                                    <TableRow key={deposit.id}>
                                        <TableCell className="font-medium">
                                            {deposit.penyewa}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(deposit.held_at)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatRupiah(deposit.amount)}
                                        </TableCell>
                                        <TableCell className="text-right font-medium">
                                            {formatRupiah(deposit.balance)}
                                        </TableCell>
                                        <TableCell>
                                            <ColorBadge
                                                label={deposit.status_label}
                                                color={deposit.status_color}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <ModalLink
                                                href={
                                                    deposits.show(deposit.id)
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

DepositsIndex.layout = {
    breadcrumbs: [{ title: 'Deposit', href: deposits.index() }],
};
