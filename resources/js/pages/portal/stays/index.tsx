import { Head, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { History } from 'lucide-react';
import { Pagination } from '@/components/master/pagination';
import { ColorBadge } from '@/components/master/status-badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate, formatRupiah } from '@/lib/format';
import portalStays from '@/routes/portal/stays';
import type { Paginated } from '@/types/master';
import type { PortalStayListItem } from '@/types/portal';

type PageProps = {
    stays: Paginated<PortalStayListItem>;
    summary: { total: number; active_room: string | null };
};

export default function PortalStaysIndex() {
    const { stays, summary } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Riwayat Kamar" />

            <div className="flex flex-col gap-6">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Riwayat Kamar
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {summary.active_room
                            ? `Kamu sedang menempati kamar ${summary.active_room}.`
                            : 'Riwayat booking dan masa sewa kamu.'}
                    </p>
                </div>

                {stays.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-muted-foreground">
                            <History className="size-8" />
                            <p>Belum ada riwayat kamar.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-3">
                        {stays.data.map((stay) => (
                            <Card key={stay.id}>
                                <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">
                                                {stay.room ?? 'Kamar dihapus'}
                                            </span>
                                            <ColorBadge
                                                label={stay.status_label}
                                                color={stay.status_color}
                                            />
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {formatDate(stay.start_date)} —{' '}
                                            {formatDate(stay.end_date)}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-4">
                                        <p className="font-semibold">
                                            {formatRupiah(stay.monthly_price)}
                                            <span className="text-sm font-normal text-muted-foreground">
                                                /bln
                                            </span>
                                        </p>
                                        <ModalLink
                                            href={portalStays.show(stay.id).url}
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
                    links={stays.links}
                    from={stays.from}
                    to={stays.to}
                    total={stays.total}
                />
            </div>
        </>
    );
}

PortalStaysIndex.layout = {
    breadcrumbs: [{ title: 'Riwayat Kamar', href: portalStays.index() }],
};
