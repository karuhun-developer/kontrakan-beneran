import { Head, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { Wallet } from 'lucide-react';
import { Pagination } from '@/components/master/pagination';
import { ColorBadge } from '@/components/master/status-badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, formatRupiah } from '@/lib/format';
import portalDeposits from '@/routes/portal/deposits';
import type { Paginated } from '@/types/master';
import type { PortalDepositListItem } from '@/types/portal';

type PageProps = {
    deposits: Paginated<PortalDepositListItem>;
    summary: { active_balance: number; total_held: number };
};

export default function PortalDepositsIndex() {
    const { deposits, summary } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Deposit" />

            <div className="flex flex-col gap-6">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Deposit
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Saldo deposit yang ditahan beserta riwayat potongan &
                        pengembalian.
                    </p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Saldo Aktif
                            </CardTitle>
                            <Wallet className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-lg font-semibold">
                                {formatRupiah(summary.active_balance)}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Total Ditahan
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-lg font-semibold">
                                {formatRupiah(summary.total_held)}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {deposits.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-muted-foreground">
                            <Wallet className="size-8" />
                            <p>Belum ada deposit.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-3">
                        {deposits.data.map((deposit) => (
                            <Card key={deposit.id}>
                                <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">
                                                {formatRupiah(deposit.balance)}
                                            </span>
                                            <ColorBadge
                                                label={deposit.status_label}
                                                color={deposit.status_color}
                                            />
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            Ditahan{' '}
                                            {formatRupiah(deposit.amount)}
                                            {deposit.held_at
                                                ? ` · ${formatDate(deposit.held_at)}`
                                                : ''}
                                        </p>
                                    </div>
                                    <ModalLink
                                        href={
                                            portalDeposits.show(deposit.id).url
                                        }
                                        className={buttonVariants({
                                            variant: 'outline',
                                            size: 'sm',
                                        })}
                                    >
                                        Detail
                                    </ModalLink>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Pagination
                    links={deposits.links}
                    from={deposits.from}
                    to={deposits.to}
                    total={deposits.total}
                />
            </div>
        </>
    );
}

PortalDepositsIndex.layout = {
    breadcrumbs: [{ title: 'Deposit', href: portalDeposits.index() }],
};
