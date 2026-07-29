import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    Tags,
    Wallet,
} from 'lucide-react';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { RowActions } from '@/components/master/row-actions';
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
import cashflowCategories from '@/routes/cashflow-categories';
import cashflows from '@/routes/cashflows';
import type {
    CashflowListItem,
    CashflowStats,
    Paginated,
    SelectOption,
} from '@/types/master';

type PageProps = {
    cashflows: Paginated<CashflowListItem>;
    stats: CashflowStats;
    filters: { type: string | null };
    typeOptions: SelectOption[];
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

export default function CashflowsIndex() {
    const {
        cashflows: data,
        stats,
        filters,
        typeOptions,
    } = usePage<PageProps>().props;
    const canManage = useCan('cashflow.manage');

    const applyType = (type: string | null) => {
        router.get(cashflows.index().url, type ? { type } : {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <>
            <Head title="Kas" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Kas"
                    description="Catat pemasukan & pengeluaran, dan pantau saldo kas cabang."
                    action={
                        canManage
                            ? {
                                  label: 'Catat Transaksi',
                                  href: cashflows.create(),
                              }
                            : undefined
                    }
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        icon={Wallet}
                        label="Saldo Kas"
                        value={formatRupiah(stats.balance)}
                        tone="bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                    />
                    <StatCard
                        icon={ArrowUpCircle}
                        label="Pemasukan Bulan Ini"
                        value={formatRupiah(stats.income_this_month)}
                        tone="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
                    />
                    <StatCard
                        icon={ArrowDownCircle}
                        label="Pengeluaran Bulan Ini"
                        value={formatRupiah(stats.expense_this_month)}
                        tone="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300"
                    />
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant={filters.type ? 'outline' : 'default'}
                        onClick={() => applyType(null)}
                    >
                        Semua
                    </Button>
                    {typeOptions.map((option) => (
                        <Button
                            key={option.value}
                            type="button"
                            size="sm"
                            variant={
                                filters.type === option.value
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() => applyType(String(option.value))}
                        >
                            {option.label}
                        </Button>
                    ))}
                    <Link
                        href={cashflowCategories.index().url}
                        className={cn(
                            buttonVariants({ variant: 'ghost', size: 'sm' }),
                            'ml-auto',
                        )}
                    >
                        <Tags className="size-4" />
                        Kelola Kategori
                    </Link>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Deskripsi</TableHead>
                                    <TableHead>Kategori</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead className="text-right">
                                        Jumlah
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada transaksi kas.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((cashflow) => (
                                    <TableRow key={cashflow.id}>
                                        <TableCell className="whitespace-nowrap text-sm text-muted-foreground">
                                            {formatDate(cashflow.occurred_on)}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {cashflow.description}
                                            {cashflow.is_system && (
                                                <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                    (otomatis)
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {cashflow.category ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <ColorBadge
                                                label={cashflow.type_label}
                                                color={cashflow.type_color}
                                            />
                                        </TableCell>
                                        <TableCell
                                            className={cn(
                                                'text-right font-medium',
                                                cashflow.type === 'income'
                                                    ? 'text-green-600 dark:text-green-400'
                                                    : 'text-red-600 dark:text-red-400',
                                            )}
                                        >
                                            {cashflow.type === 'income'
                                                ? '+'
                                                : '−'}
                                            {formatRupiah(cashflow.amount)}
                                        </TableCell>
                                        <TableCell>
                                            {cashflow.is_system ? (
                                                <span className="block text-right text-xs text-muted-foreground">
                                                    —
                                                </span>
                                            ) : (
                                                <RowActions
                                                    label={
                                                        cashflow.description
                                                    }
                                                    editHref={cashflows.edit(
                                                        cashflow.id,
                                                    )}
                                                    destroyRoute={cashflows.destroy(
                                                        cashflow.id,
                                                    )}
                                                    canManage={canManage}
                                                />
                                            )}
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

CashflowsIndex.layout = {
    breadcrumbs: [{ title: 'Kas', href: cashflows.index() }],
};
