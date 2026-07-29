import { Head, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import {
    BedDouble,
    Building2,
    ReceiptText,
    TrendingDown,
    TrendingUp,
    Wallet,
} from 'lucide-react';
import { ColorBadge } from '@/components/master/status-badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    formatDate,
    formatRupiah,
    formatRupiahShort,
} from '@/lib/format';
import { dashboard } from '@/routes';
import invoices from '@/routes/invoices';

type TrendPoint = {
    month: string;
    label: string;
    income: number;
    expense: number;
};

type Analytics = {
    occupancy: {
        total: number;
        occupancy_rate: number;
        statuses: {
            status: string;
            label: string;
            color: string;
            count: number;
        }[];
    };
    active_penyewa: number;
    billing: {
        outstanding_count: number;
        outstanding_value: number;
        collected_this_month: number;
    };
    cashflow: { income: number; expense: number; net: number };
    trend: TrendPoint[];
    due_soon: {
        id: number;
        number: string;
        penyewa: string | null;
        due_date: string;
        outstanding: number;
    }[];
};

type PageProps = {
    currentBranch: { id: number; name: string } | null;
    analytics: Analytics | null;
};

function StatCard({
    title,
    value,
    hint,
    icon: Icon,
}: {
    title: string;
    value: string;
    hint?: string;
    icon: React.ComponentType<{ className?: string }>;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
                <Icon className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <p className="text-2xl font-semibold">{value}</p>
                {hint && (
                    <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
                )}
            </CardContent>
        </Card>
    );
}

export default function Dashboard() {
    const { currentBranch, analytics } = usePage<PageProps>().props;

    if (!analytics) {
        return (
            <>
                <Head title="Dashboard" />
                <div className="p-4">
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-muted-foreground">
                            <Building2 className="size-8" />
                            <p>
                                Pilih cabang terlebih dahulu untuk melihat
                                ringkasan.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </>
        );
    }

    const { occupancy, active_penyewa, billing, cashflow, trend, due_soon } =
        analytics;
    const occupiedCount =
        occupancy.statuses.find((s) => s.status === 'occupied')?.count ?? 0;
    const trendMax = Math.max(
        1,
        ...trend.map((point) => Math.max(point.income, point.expense)),
    );

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-4 p-4">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    {currentBranch && (
                        <p className="text-sm text-muted-foreground">
                            Ringkasan cabang {currentBranch.name}.
                        </p>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Okupansi"
                        value={`${occupancy.occupancy_rate}%`}
                        hint={`${occupiedCount} dari ${occupancy.total} kamar terisi`}
                        icon={BedDouble}
                    />
                    <StatCard
                        title="Penyewa Aktif"
                        value={String(active_penyewa)}
                        hint="Sedang menempati kamar"
                        icon={Building2}
                    />
                    <StatCard
                        title="Tertunggak"
                        value={formatRupiah(billing.outstanding_value)}
                        hint={`${billing.outstanding_count} tagihan belum lunas`}
                        icon={ReceiptText}
                    />
                    <StatCard
                        title="Kas Bulan Ini"
                        value={formatRupiah(cashflow.net)}
                        hint={`Masuk ${formatRupiahShort(cashflow.income)} · Keluar ${formatRupiahShort(cashflow.expense)}`}
                        icon={Wallet}
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
                                Status Kamar
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {occupancy.statuses.map((status) => (
                                <div
                                    key={status.status}
                                    className="flex items-center justify-between"
                                >
                                    <ColorBadge
                                        label={status.label}
                                        color={status.color}
                                    />
                                    <span className="text-sm font-medium">
                                        {status.count}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-base">
                            Tagihan Jatuh Tempo (7 hari)
                        </CardTitle>
                        <span className="text-sm text-muted-foreground">
                            Diterima bulan ini{' '}
                            {formatRupiah(billing.collected_this_month)}
                        </span>
                    </CardHeader>
                    <CardContent className="p-0">
                        {due_soon.length === 0 ? (
                            <p className="px-6 py-10 text-center text-sm text-muted-foreground">
                                Tidak ada tagihan jatuh tempo minggu ini.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {due_soon.map((invoice) => (
                                    <li
                                        key={invoice.id}
                                        className="flex items-center justify-between gap-4 px-6 py-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-medium">
                                                {invoice.penyewa ?? '—'}
                                            </p>
                                            <p className="font-mono text-xs text-muted-foreground">
                                                {invoice.number} ·{' '}
                                                {formatDate(invoice.due_date)}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-sm font-medium whitespace-nowrap">
                                                {formatRupiah(
                                                    invoice.outstanding,
                                                )}
                                            </span>
                                            <ModalLink
                                                href={
                                                    invoices.show(invoice.id).url
                                                }
                                                className="text-sm font-medium text-primary hover:underline"
                                            >
                                                Lihat
                                            </ModalLink>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
