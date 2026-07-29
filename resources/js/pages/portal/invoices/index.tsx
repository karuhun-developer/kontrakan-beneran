import { Head, router, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { ReceiptText } from 'lucide-react';
import { Pagination } from '@/components/master/pagination';
import { ColorBadge } from '@/components/master/status-badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate, formatRupiah } from '@/lib/format';
import portalInvoices from '@/routes/portal/invoices';
import type { Paginated, SelectOption } from '@/types/master';
import type { PortalInvoiceListItem } from '@/types/portal';

type PageProps = {
    invoices: Paginated<PortalInvoiceListItem>;
    filters: { status: string | null };
    statusOptions: SelectOption[];
};

export default function PortalInvoicesIndex() {
    const { invoices, filters, statusOptions } =
        usePage<PageProps>().props;

    const applyStatus = (status: string | null) => {
        router.get(portalInvoices.index().url, status ? { status } : {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <>
            <Head title="Tagihan" />

            <div className="flex flex-col gap-6">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Tagihan
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Daftar tagihan kamu — bayar online langsung dari sini.
                    </p>
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

                {invoices.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-16 text-center text-muted-foreground">
                            <ReceiptText className="size-8" />
                            <p>Belum ada tagihan.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="flex flex-col gap-3">
                        {invoices.data.map((invoice) => (
                            <Card key={invoice.id}>
                                <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-mono text-sm">
                                                {invoice.number}
                                            </span>
                                            <ColorBadge
                                                label={invoice.status_label}
                                                color={invoice.status_color}
                                            />
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {formatDate(invoice.period_start)} –{' '}
                                            {formatDate(invoice.period_end)} ·
                                            Jatuh tempo{' '}
                                            {formatDate(invoice.due_date)}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-4">
                                        <div className="text-right">
                                            <p className="font-semibold">
                                                {formatRupiah(invoice.total)}
                                            </p>
                                            {invoice.outstanding > 0 && (
                                                <p className="text-xs text-red-600 dark:text-red-400">
                                                    Sisa{' '}
                                                    {formatRupiah(
                                                        invoice.outstanding,
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                        <ModalLink
                                            href={
                                                portalInvoices.show(invoice.id)
                                                    .url
                                            }
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
                    links={invoices.links}
                    from={invoices.from}
                    to={invoices.to}
                    total={invoices.total}
                />
            </div>
        </>
    );
}

PortalInvoicesIndex.layout = {
    breadcrumbs: [
        { title: 'Tagihan', href: portalInvoices.index() },
    ],
};
