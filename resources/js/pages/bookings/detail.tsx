import { router } from '@inertiajs/react';
import { CalendarPlus, LogIn, LogOut, XCircle } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { ModalForm } from '@/components/master/modal-form';
import { ColorBadge } from '@/components/master/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, formatDateTime, formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import bookings from '@/routes/bookings';
import type { BookingDetail } from '@/types/master';

type Panel = 'extend' | 'cancel' | null;

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

export default function BookingDetailModal({
    booking,
}: {
    booking: BookingDetail;
}) {
    const [panel, setPanel] = useState<Panel>(null);
    const [endDate, setEndDate] = useState('');
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const submit = (
        url: string,
        data: Record<string, string>,
        close: () => void,
    ) => {
        setProcessing(true);
        setErrors({});
        router.post(url, data, {
            preserveScroll: true,
            onSuccess: () => close(),
            onError: (formErrors) => setErrors(formErrors),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <ModalForm title="Detail Booking" description={booking.room}>
            {(close) => (
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-lg font-semibold">
                                {booking.penyewa}
                            </p>
                            {booking.penyewa_phone && (
                                <p className="text-sm text-muted-foreground">
                                    {booking.penyewa_phone}
                                </p>
                            )}
                        </div>
                        <ColorBadge
                            label={booking.status_label}
                            color={booking.status_color}
                        />
                    </div>

                    <div className="rounded-lg border divide-y px-4">
                        <InfoRow
                            label="Tanggal Mulai"
                            value={formatDate(booking.start_date)}
                        />
                        <InfoRow
                            label="Tanggal Selesai"
                            value={formatDate(booking.end_date)}
                        />
                        <InfoRow
                            label="Harga / Bulan"
                            value={formatRupiah(booking.monthly_price)}
                        />
                        {booking.checked_in_at && (
                            <InfoRow
                                label="Check-in"
                                value={formatDateTime(booking.checked_in_at)}
                            />
                        )}
                        {booking.checked_out_at && (
                            <InfoRow
                                label="Check-out"
                                value={formatDateTime(booking.checked_out_at)}
                            />
                        )}
                    </div>

                    {booking.notes && (
                        <div className="rounded-lg bg-muted/50 p-3 text-sm">
                            <p className="mb-1 font-medium">Catatan</p>
                            <p className="text-muted-foreground">
                                {booking.notes}
                            </p>
                        </div>
                    )}

                    {errors.booking && <InputError message={errors.booking} />}

                    {/* Actions */}
                    <div className="flex flex-wrap gap-2">
                        {booking.can_check_in && (
                            <Button
                                type="button"
                                disabled={processing}
                                onClick={() =>
                                    submit(
                                        bookings.checkIn(booking.id).url,
                                        {},
                                        close,
                                    )
                                }
                            >
                                <LogIn className="size-4" />
                                Check-in
                            </Button>
                        )}
                        {booking.can_check_out && (
                            <Button
                                type="button"
                                disabled={processing}
                                onClick={() =>
                                    submit(
                                        bookings.checkOut(booking.id).url,
                                        {},
                                        close,
                                    )
                                }
                            >
                                <LogOut className="size-4" />
                                Check-out
                            </Button>
                        )}
                        {booking.can_extend && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    setPanel(panel === 'extend' ? null : 'extend')
                                }
                            >
                                <CalendarPlus className="size-4" />
                                Perpanjang
                            </Button>
                        )}
                        {booking.can_cancel && (
                            <Button
                                type="button"
                                variant="outline"
                                className="text-red-600 hover:text-red-600"
                                onClick={() =>
                                    setPanel(panel === 'cancel' ? null : 'cancel')
                                }
                            >
                                <XCircle className="size-4" />
                                Batalkan
                            </Button>
                        )}
                    </div>

                    {panel === 'extend' && (
                        <div className="space-y-3 rounded-lg border p-4">
                            <Label htmlFor="end_date">
                                Perpanjang sampai tanggal
                            </Label>
                            <Input
                                id="end_date"
                                type="date"
                                value={endDate}
                                min={booking.end_date}
                                onChange={(e) => setEndDate(e.target.value)}
                            />
                            <InputError message={errors.end_date} />
                            <Button
                                type="button"
                                disabled={processing || !endDate}
                                onClick={() =>
                                    submit(
                                        bookings.extend(booking.id).url,
                                        { end_date: endDate },
                                        close,
                                    )
                                }
                            >
                                Simpan Perpanjangan
                            </Button>
                        </div>
                    )}

                    {panel === 'cancel' && (
                        <div className="space-y-3 rounded-lg border border-red-200 p-4">
                            <Label htmlFor="reason">Alasan pembatalan</Label>
                            <Textarea
                                id="reason"
                                value={reason}
                                rows={2}
                                placeholder="Opsional"
                                onChange={(e) => setReason(e.target.value)}
                            />
                            <InputError message={errors.reason} />
                            <Button
                                type="button"
                                variant="destructive"
                                disabled={processing}
                                onClick={() =>
                                    submit(
                                        bookings.cancel(booking.id).url,
                                        { reason },
                                        close,
                                    )
                                }
                            >
                                Konfirmasi Pembatalan
                            </Button>
                        </div>
                    )}

                    {/* History */}
                    <div>
                        <p className="mb-3 text-sm font-medium">Riwayat</p>
                        <ol className="space-y-4">
                            {booking.logs.map((log, index) => (
                                <li key={log.id} className="flex gap-3">
                                    <div className="flex flex-col items-center">
                                        <span
                                            className={cn(
                                                'mt-1 size-2.5 rounded-full',
                                                index === 0
                                                    ? 'bg-primary'
                                                    : 'bg-muted-foreground/40',
                                            )}
                                        />
                                        {index < booking.logs.length - 1 && (
                                            <span className="w-px flex-1 bg-border" />
                                        )}
                                    </div>
                                    <div className="-mt-0.5 pb-1">
                                        <p className="text-sm font-medium">
                                            {log.event_label}
                                        </p>
                                        {log.description && (
                                            <p className="text-sm text-muted-foreground">
                                                {log.description}
                                            </p>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            {formatDateTime(log.created_at)}
                                            {log.user ? ` · ${log.user}` : ''}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ol>
                    </div>

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
