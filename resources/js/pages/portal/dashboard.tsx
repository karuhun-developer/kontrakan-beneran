import { Head, Link, usePage } from '@inertiajs/react';
import { Building2, DoorOpen, ReceiptText, Wallet } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatRupiah } from '@/lib/format';
import portalInvoices from '@/routes/portal/invoices';
import type { Auth } from '@/types';

type PortalSummary = {
    room: string | null;
    outstanding_count: number;
    outstanding_value: number;
    deposit_balance: number;
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
        },
        {
            title: 'Tagihan Tertunggak',
            icon: ReceiptText,
            value:
                summary && summary.outstanding_count > 0
                    ? `${formatRupiah(summary.outstanding_value)} · ${summary.outstanding_count} tagihan`
                    : 'Tidak ada tunggakan',
        },
        {
            title: 'Saldo Deposit',
            icon: Wallet,
            value: formatRupiah(summary?.deposit_balance ?? 0),
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
                    {cards.map((card) => (
                        <Card key={card.title}>
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
                    ))}
                </div>

                {summary && summary.outstanding_count > 0 && (
                    <Card>
                        <CardContent className="flex flex-wrap items-center justify-between gap-4 py-5">
                            <div>
                                <p className="font-medium">
                                    Kamu punya {summary.outstanding_count}{' '}
                                    tagihan yang belum lunas.
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Total {formatRupiah(summary.outstanding_value)} menunggu pembayaran.
                                </p>
                            </div>
                            <Button asChild>
                                <Link href={portalInvoices.index().url}>
                                    Lihat Tagihan
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
