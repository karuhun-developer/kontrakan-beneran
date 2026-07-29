import { router } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { ModalForm } from '@/components/master/modal-form';
import { ColorBadge } from '@/components/master/status-badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { formatDate, formatRupiah } from '@/lib/format';
import laundry from '@/routes/laundry';
import type { LaundryDetail } from '@/types/master';

function InfoRow({
    label,
    value,
}: {
    label: string;
    value: React.ReactNode;
}) {
    return (
        <div className="flex items-start justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

export default function LaundryDetailModal({
    order,
}: {
    order: LaundryDetail;
}) {
    const canManage = useCan('laundry.manage');
    const [processing, setProcessing] = useState(false);

    const advance = (close: () => void) => {
        setProcessing(true);
        router.post(
            laundry.advance(order.id).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => close(),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <ModalForm title={`Laundry ${order.number}`} description={order.penyewa}>
            {(close) => (
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <p className="text-lg font-semibold">
                                {order.penyewa}
                            </p>
                            {order.penyewa_phone && (
                                <p className="text-sm text-muted-foreground">
                                    {order.penyewa_phone}
                                </p>
                            )}
                        </div>
                        <ColorBadge
                            label={order.status_label}
                            color={order.status_color}
                        />
                    </div>

                    <div className="flex items-center justify-between rounded-lg bg-muted/50 p-4">
                        <span className="text-sm font-semibold">Total Harga</span>
                        <span className="text-base font-semibold">
                            {formatRupiah(order.price)}
                        </span>
                    </div>

                    <div className="divide-y rounded-lg border px-4">
                        <InfoRow
                            label="Tanggal Order"
                            value={formatDate(order.ordered_on)}
                        />
                        {order.weight !== null && (
                            <InfoRow
                                label="Berat"
                                value={`${order.weight} kg`}
                            />
                        )}
                        <InfoRow
                            label="Pembayaran"
                            value={
                                <ColorBadge
                                    label={order.payment_source_label}
                                    color={order.payment_source_color}
                                />
                            }
                        />
                        {order.invoice_number && (
                            <InfoRow
                                label="Tagihan"
                                value={order.invoice_number}
                            />
                        )}
                        {order.recorded_by && (
                            <InfoRow
                                label="Dicatat oleh"
                                value={order.recorded_by}
                            />
                        )}
                        {order.notes && (
                            <InfoRow label="Catatan" value={order.notes} />
                        )}
                    </div>

                    {canManage && order.can_advance && order.next_status_label && (
                        <Button
                            type="button"
                            disabled={processing}
                            onClick={() => advance(close)}
                        >
                            <ArrowRight className="size-4" />
                            Tandai {order.next_status_label}
                        </Button>
                    )}

                    <div className="flex justify-end pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={close}
                        >
                            Tutup
                        </Button>
                    </div>
                </div>
            )}
        </ModalForm>
    );
}
