import { router } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import { useState } from 'react';
import { ModalForm } from '@/components/master/modal-form';
import { ColorBadge } from '@/components/master/status-badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatDateTime, formatRupiah } from '@/lib/format';
import portalInvoices from '@/routes/portal/invoices';
import type { PortalInvoiceDetail } from '@/types/portal';

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

function TotalRow({
    label,
    value,
    strong,
}: {
    label: string;
    value: string;
    strong?: boolean;
}) {
    return (
        <div className="flex items-center justify-between text-sm">
            <span className={strong ? 'font-semibold' : 'text-muted-foreground'}>
                {label}
            </span>
            <span className={strong ? 'text-base font-semibold' : 'font-medium'}>
                {value}
            </span>
        </div>
    );
}

export default function PortalInvoiceDetailModal({
    invoice,
}: {
    invoice: PortalInvoiceDetail;
}) {
    const [processing, setProcessing] = useState(false);

    const payOnline = (close: () => void) => {
        setProcessing(true);
        router.post(
            portalInvoices.checkout(invoice.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => close(),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <ModalForm title={invoice.number} description={invoice.room ?? undefined}>
            {(close) => (
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <p className="text-lg font-semibold">
                                {invoice.number}
                            </p>
                            {invoice.room && (
                                <p className="text-sm text-muted-foreground">
                                    {invoice.room}
                                </p>
                            )}
                        </div>
                        <ColorBadge
                            label={invoice.status_label}
                            color={invoice.status_color}
                        />
                    </div>

                    <div className="divide-y rounded-lg border px-4">
                        <InfoRow
                            label="Periode"
                            value={`${formatDate(invoice.period_start)} – ${formatDate(invoice.period_end)}`}
                        />
                        <InfoRow
                            label="Jatuh Tempo"
                            value={formatDate(invoice.due_date)}
                        />
                    </div>

                    <div className="space-y-2">
                        <p className="text-sm font-medium">Rincian</p>
                        <div className="divide-y rounded-lg border">
                            {invoice.items.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex items-start justify-between gap-4 px-4 py-2.5 text-sm"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {item.description}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {item.type_label}
                                            {item.quantity > 1
                                                ? ` · ${item.quantity} × ${formatRupiah(item.unit_price)}`
                                                : ''}
                                        </p>
                                    </div>
                                    <span className="font-medium whitespace-nowrap">
                                        {formatRupiah(item.amount)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="space-y-2 rounded-lg bg-muted/50 p-4">
                        <TotalRow
                            label="Subtotal"
                            value={formatRupiah(invoice.subtotal)}
                        />
                        {invoice.discount > 0 && (
                            <TotalRow
                                label="Diskon"
                                value={`- ${formatRupiah(invoice.discount)}`}
                            />
                        )}
                        {invoice.fine > 0 && (
                            <TotalRow
                                label="Denda"
                                value={formatRupiah(invoice.fine)}
                            />
                        )}
                        <TotalRow
                            label="Total"
                            value={formatRupiah(invoice.total)}
                            strong
                        />
                        <TotalRow
                            label="Dibayar"
                            value={formatRupiah(invoice.paid)}
                        />
                        <TotalRow
                            label="Sisa"
                            value={formatRupiah(invoice.outstanding)}
                            strong
                        />
                    </div>

                    {invoice.can_pay && (
                        <Button
                            type="button"
                            className="w-full"
                            disabled={processing}
                            onClick={() => payOnline(close)}
                        >
                            <CreditCard className="size-4" />
                            Bayar Online
                        </Button>
                    )}

                    <div>
                        <p className="mb-3 text-sm font-medium">
                            Riwayat Pembayaran
                        </p>
                        {invoice.payments.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada pembayaran.
                            </p>
                        ) : (
                            <ol className="space-y-3">
                                {invoice.payments.map((payment) => (
                                    <li
                                        key={payment.id}
                                        className="flex items-start justify-between gap-4 rounded-lg border px-4 py-2.5 text-sm"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {formatRupiah(payment.amount)}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {payment.method_label}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDateTime(payment.paid_at)}
                                            </p>
                                        </div>
                                        <ColorBadge
                                            label={payment.status_label}
                                            color={payment.status_color}
                                        />
                                    </li>
                                ))}
                            </ol>
                        )}
                    </div>

                    <div className="flex justify-end pt-2">
                        <Button type="button" variant="outline" onClick={close}>
                            Tutup
                        </Button>
                    </div>
                </div>
            )}
        </ModalForm>
    );
}
