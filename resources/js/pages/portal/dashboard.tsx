import { Head, Link, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import {
    Building2,
    CalendarClock,
    DoorOpen,
    ReceiptText,
    Wallet,
} from 'lucide-react';
import { ColorBadge } from '@/components/master/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate, formatRupiah } from '@/lib/format';
import portalDeposits from '@/routes/portal/deposits';
import portalInvoices from '@/routes/portal/invoices';
import type { Auth } from '@/types';

type RecentInvoice = {
    id: number;
    number: string;
    total: number;
    due_date: string;
    status: string;
    status_label: string;
    status_color: string;
};

type PortalSummary = {
    room: string | null;
    outstanding_count: number;
    outstanding_value: number;
    deposit_balance: number;
    next_due: {
        id: number;
        number: string;
        due_date: string;
        outstanding: number;
    } | null;
    recent_invoices: RecentInvoice[];
};

type PortalDashboardProps = {
    auth: Auth;
    penyewa: {
        id: number;
        name: string;
        branch: string | null;
    } | null;
    summary: PortalSummary | null;
};

export default function PortalDashboard() {
    const { penyewa, auth, summary } = usePage<PortalDashboardProps>().props;
    const displayName = penyewa?.name ?? auth.user?.name ?? 'Penyewa';

    const cards = [
        {
            title: 'Kamar Saya',
            icon: DoorOpen,
            value: summary?.room ?? 'Belum ada kamar aktif',
            href: null,
        },
        {
            title: 'Tagihan Tertunggak',
            icon: ReceiptText,
            value:
                summary && summary.outstanding_count > 0
                    ? `${formatRupiah(summary.outstanding_value)} · ${summary.outstanding_count} tagihan`
                    : 'Tidak ada tunggakan',
            href: portalInvoices.index().url,
        },
        {
            title: 'Saldo Deposit',
            icon: Wallet,
            value: formatRupiah(summary?.deposit_balance ?? 0),
            href: portalDeposits.index().url,
        },
    ];

    return (
        <>
            <Head title="Portal" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Halo, {displayName}
                    </h1>
                    {penyewa?.branch && (
                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <Building2 className="size-4" />
                            {penyewa.branch}
                        </p>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {cards.map((card) => {
                        const body = (
                            <Card
                                className={
                                    card.href
                                        ? 'transition-colors hover:border-primary/40'
                                        : undefined
                                }
                            >
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        {card.title}
                                    </CardTitle>
                                    <card.icon className="size-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <p className="text-lg font-semibold">
                                        {card.value}
                                    </p>
                                </CardContent>
                            </Card>
                        );

                        return card.href ? (
                            <Link
                                key={card.title}
                                href={card.href}
                                className="block"
                            >
                                {body}
                            </Link>
                        ) : (
                            <div key={card.title}>{body}</div>
                        );
                    })}
                </div>

                {summary?.next_due && (
                    <Card className="border-primary/30 bg-primary/5">
                        <CardContent className="flex flex-wrap items-center justify-between gap-4 py-5">
                            <div className="flex items-start gap-3">
                                <CalendarClock className="mt-0.5 size-5 text-primary" />
                                <div>
                                    <p className="font-medium">
                                        Tagihan berikutnya jatuh tempo{' '}
                                        {formatDate(summary.next_due.due_date)}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {summary.next_due.number} ·{' '}
                                        {formatRupiah(
                                            summary.next_due.outstanding,
                                        )}{' '}
                                        menunggu pembayaran
                                        {summary.outstanding_count > 1 &&
                                            ` · ${summary.outstanding_count - 1} tagihan lain`}
                                    </p>
                                </div>
                            </div>
                            <Button asChild>
                                <ModalLink
                                    href={
                                        portalInvoices.show(summary.next_due.id)
                                            .url
                                    }
                                >
                                    Bayar Sekarang
                                </ModalLink>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {summary && summary.recent_invoices.length > 0 && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0">
                            <CardTitle className="text-base">
                                Tagihan Terbaru
                            </CardTitle>
                            <Link
                                href={portalInvoices.index().url}
                                className="text-sm font-medium text-primary hover:underline"
                            >
                                Semua
                            </Link>
                        </CardHeader>
                        <CardContent className="p-0">
                            <ul className="divide-y">
                                {summary.recent_invoices.map((invoice) => (
                                    <li
                                        key={invoice.id}
                                        className="flex items-center justify-between gap-4 px-6 py-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate font-mono text-sm font-medium">
                                                {invoice.number}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Jatuh tempo{' '}
                                                {formatDate(invoice.due_date)}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <ColorBadge
                                                label={invoice.status_label}
                                                color={invoice.status_color}
                                            />
                                            <span className="text-sm font-medium whitespace-nowrap">
                                                {formatRupiah(invoice.total)}
                                            </span>
                                            <ModalLink
                                                href={
                                                    portalInvoices.show(
                                                        invoice.id,
                                                    ).url
                                                }
                                                className="text-sm font-medium text-primary hover:underline"
                                            >
                                                Lihat
                                            </ModalLink>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
