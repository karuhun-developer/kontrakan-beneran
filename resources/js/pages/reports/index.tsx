import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    Download,
    Scale,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';
import { PageHeader } from '@/components/master/page-header';
import { ColorBadge } from '@/components/master/status-badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { formatRupiah, formatRupiahShort } from '@/lib/format';
import reports from '@/routes/reports';

type TrendPoint = {
    month: string;
    label: string;
    income: number;
    expense: number;
};

type CategoryRow = {
    name: string;
    type: string;
    type_label: string;
    type_color: string;
    total: number;
};

type PageProps = {
    filters: { start: string; end: string };
    summary: { income: number; expense: number; net: number };
    categories: CategoryRow[];
    trend: TrendPoint[];
};

function StatCard({
    icon: Icon,
    label,
    value,
    tone,
}: {
    icon: typeof Scale;
    label: string;
    value: string;
    tone: string;
}) {
    return (
        <Card>
            <CardContent className="flex items-center gap-4 py-5">
                <div
                    className={cn(
                        'flex size-11 items-center justify-center rounded-lg',
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

export default function ReportsIndex() {
    const { filters, summary, categories, trend } =
        usePage<PageProps>().props;
    const [start, setStart] = useState(filters.start);
    const [end, setEnd] = useState(filters.end);

    const applyRange = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            reports.index().url,
            { start, end },
            { preserveScroll: true, preserveState: true },
        );
    };

    const trendMax = Math.max(
        1,
        ...trend.map((point) => Math.max(point.income, point.expense)),
    );

    return (
        <>
            <Head title="Laporan" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Laporan Keuangan"
                    description="Ringkasan pemasukan & pengeluaran cabang untuk periode terpilih."
                />

                <Card>
                    <CardContent className="py-5">
                        <form
                            onSubmit={applyRange}
                            className="flex flex-wrap items-end gap-3"
                        >
                            <div className="grid gap-1.5">
                                <Label htmlFor="start">Dari Tanggal</Label>
                                <Input
                                    id="start"
                                    type="date"
                                    value={start}
                                    max={end}
                                    onChange={(e) => setStart(e.target.value)}
                                    className="w-auto"
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="end">Sampai Tanggal</Label>
                                <Input
                                    id="end"
                                    type="date"
                                    value={end}
                                    min={start}
                                    onChange={(e) => setEnd(e.target.value)}
                                    className="w-auto"
                                />
                            </div>
                            <Button type="submit">Terapkan</Button>
                            <a
                                href={reports.export({ query: { start, end } }).url}
                                className={cn(
                                    buttonVariants({ variant: 'outline' }),
                                    'ml-auto',
                                )}
                            >
                                <Download className="size-4" />
                                Ekspor CSV
                            </a>
                        </form>
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        icon={ArrowUpCircle}
                        label="Pemasukan"
                        value={formatRupiah(summary.income)}
                        tone="bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
                    />
                    <StatCard
                        icon={ArrowDownCircle}
                        label="Pengeluaran"
                        value={formatRupiah(summary.expense)}
                        tone="bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300"
                    />
                    <StatCard
                        icon={Scale}
                        label="Selisih (Net)"
                        value={formatRupiah(summary.net)}
                        tone="bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Pemasukan vs Pengeluaran (6 bulan)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-end justify-between gap-3">
                                {trend.map((point) => (
                                    <div
                                        key={point.month}
                                        className="flex flex-1 flex-col items-center gap-2"
                                    >
                                        <div className="flex h-32 w-full items-end justify-center gap-1">
                                            <div
                                                className="w-3 rounded-t bg-green-500/80"
                                                style={{
                                                    height: `${(point.income / trendMax) * 100}%`,
                                                }}
                                                title={`Masuk ${formatRupiah(point.income)}`}
                                            />
                                            <div
                                                className="w-3 rounded-t bg-red-500/70"
                                                style={{
                                                    height: `${(point.expense / trendMax) * 100}%`,
                                                }}
                                                title={`Keluar ${formatRupiah(point.expense)}`}
                                            />
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {point.label}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            <div className="mt-4 flex items-center gap-4 text-xs text-muted-foreground">
                                <span className="flex items-center gap-1.5">
                                    <TrendingUp className="size-3.5 text-green-500" />
                                    Pemasukan
                                </span>
                                <span className="flex items-center gap-1.5">
                                    <TrendingDown className="size-3.5 text-red-500" />
                                    Pengeluaran
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Rincian per Kategori
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="px-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead className="text-right">
                                            Total
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={2}
                                                className="py-10 text-center text-muted-foreground"
                                            >
                                                Tidak ada transaksi pada periode
                                                ini.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {categories.map((row) => (
                                        <TableRow
                                            key={`${row.type}-${row.name}`}
                                        >
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <ColorBadge
                                                        label={row.type_label}
                                                        color={row.type_color}
                                                    />
                                                    <span className="font-medium">
                                                        {row.name}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell
                                                className={cn(
                                                    'text-right font-medium whitespace-nowrap',
                                                    row.type === 'income'
                                                        ? 'text-green-600 dark:text-green-400'
                                                        : 'text-red-600 dark:text-red-400',
                                                )}
                                            >
                                                {row.type === 'income'
                                                    ? '+'
                                                    : '−'}
                                                {formatRupiahShort(row.total)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

ReportsIndex.layout = {
    breadcrumbs: [{ title: 'Laporan', href: reports.index() }],
};
