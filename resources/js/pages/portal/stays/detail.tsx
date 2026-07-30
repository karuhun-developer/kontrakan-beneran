import { ModalForm } from '@/components/master/modal-form';
import { ColorBadge } from '@/components/master/status-badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatRupiah } from '@/lib/format';
import type { PortalStayDetail } from '@/types/portal';

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

export default function PortalStayDetailModal({
    stay,
}: {
    stay: PortalStayDetail;
}) {
    return (
        <ModalForm title={stay.room ?? 'Detail Kamar'}>
            {(close) => (
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <p className="text-lg font-semibold">
                                {formatRupiah(stay.monthly_price)}
                                <span className="text-sm font-normal text-muted-foreground">
                                    /bln
                                </span>
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {formatDate(stay.start_date)} —{' '}
                                {formatDate(stay.end_date)}
                            </p>
                        </div>
                        <ColorBadge
                            label={stay.status_label}
                            color={stay.status_color}
                        />
                    </div>

                    <div className="divide-y rounded-lg border px-4">
                        {stay.checked_in_at && (
                            <InfoRow
                                label="Check-in"
                                value={formatDate(stay.checked_in_at)}
                            />
                        )}
                        {stay.checked_out_at && (
                            <InfoRow
                                label="Check-out"
                                value={formatDate(stay.checked_out_at)}
                            />
                        )}
                        {stay.notes && (
                            <InfoRow label="Catatan" value={stay.notes} />
                        )}
                    </div>

                    <div>
                        <p className="mb-3 text-sm font-medium">Riwayat</p>
                        {stay.logs.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada riwayat.
                            </p>
                        ) : (
                            <ol className="space-y-3">
                                {stay.logs.map((log) => (
                                    <li
                                        key={log.id}
                                        className="rounded-lg border px-4 py-2.5 text-sm"
                                    >
                                        <div className="flex items-center justify-between gap-4">
                                            <span className="font-medium">
                                                {log.event_label}
                                            </span>
                                            {log.occurred_on && (
                                                <span className="text-xs whitespace-nowrap text-muted-foreground">
                                                    {formatDate(
                                                        log.occurred_on,
                                                    )}
                                                </span>
                                            )}
                                        </div>
                                        {log.description && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {log.description}
                                            </p>
                                        )}
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
