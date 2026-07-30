import { ModalForm } from '@/components/master/modal-form';
import { ColorBadge } from '@/components/master/status-badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatRupiah } from '@/lib/format';
import type { PortalLaundryDetail } from '@/types/portal';

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

export default function PortalLaundryDetailModal({
    order,
}: {
    order: PortalLaundryDetail;
}) {
    return (
        <ModalForm title={order.number}>
            {(close) => (
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <p className="text-lg font-semibold">
                                {formatRupiah(order.price)}
                            </p>
                            <p className="font-mono text-sm text-muted-foreground">
                                {order.number}
                            </p>
                        </div>
                        <ColorBadge
                            label={order.status_label}
                            color={order.status_color}
                        />
                    </div>

                    <div className="divide-y rounded-lg border px-4">
                        {order.weight && (
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
                        {order.ordered_on && (
                            <InfoRow
                                label="Tanggal Order"
                                value={formatDate(order.ordered_on)}
                            />
                        )}
                        {order.notes && (
                            <InfoRow label="Catatan" value={order.notes} />
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
