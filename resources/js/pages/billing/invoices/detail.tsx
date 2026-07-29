import { router } from '@inertiajs/react';
import {
    Ban,
    Check,
    CreditCard,
    Image as ImageIcon,
    Wallet,
    X,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { ModalForm } from '@/components/master/modal-form';
import { ColorBadge } from '@/components/master/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, formatDateTime, formatRupiah } from '@/lib/format';
import invoices from '@/routes/invoices';
import invoicePayments from '@/routes/invoices/payments';
import type { InvoiceDetail, SelectOption } from '@/types/master';

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

export default function InvoiceDetailModal({
    invoice,
    paymentMethods,
}: {
    invoice: InvoiceDetail;
    paymentMethods: SelectOption[];
}) {
    const [showPay, setShowPay] = useState(false);
    const [amount, setAmount] = useState(String(invoice.outstanding));
    const [method, setMethod] = useState(
        String(paymentMethods[0]?.value ?? 'cash'),
    );
    const [reference, setReference] = useState('');
    const [notes, setNotes] = useState('');
    const [proof, setProof] = useState<File | null>(null);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const submit = (
        url: string,
        data: Record<string, string | File | null>,
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
        <ModalForm title={invoice.number} description={invoice.penyewa}>
            {(close) => (
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <p className="text-lg font-semibold">
                                {invoice.penyewa}
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

                    <div className="rounded-lg border divide-y px-4">
                        <InfoRow
                            label="Periode"
                            value={`${formatDate(invoice.period_start)} – ${formatDate(invoice.period_end)}`}
                        />
                        <InfoRow
                            label="Jatuh Tempo"
                            value={formatDate(invoice.due_date)}
                        />
                        {invoice.paid_at && (
                            <InfoRow
                                label="Lunas Pada"
                                value={formatDateTime(invoice.paid_at)}
                            />
                        )}
                    </div>

                    {/* Line items */}
                    <div className="space-y-2">
                        <p className="text-sm font-medium">Rincian</p>
                        <div className="rounded-lg border divide-y">
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
                                    <span className="whitespace-nowrap font-medium">
                                        {formatRupiah(item.amount)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Totals */}
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

                    {errors.invoice && <InputError message={errors.invoice} />}

                    {/* Actions */}
                    <div className="flex flex-wrap gap-2">
                        {invoice.can_pay && (
                            <Button
                                type="button"
                                onClick={() => setShowPay((v) => !v)}
                            >
                                <Wallet className="size-4" />
                                Catat Pembayaran
                            </Button>
                        )}
                        {invoice.can_pay && (
                            <Button
                                type="button"
                                variant="outline"
                                disabled={processing}
                                onClick={() =>
                                    submit(
                                        invoices.checkout(invoice.id).url,
                                        {},
                                        close,
                                    )
                                }
                            >
                                <CreditCard className="size-4" />
                                Bayar Online
                            </Button>
                        )}
                        {invoice.can_void && (
                            <Button
                                type="button"
                                variant="outline"
                                className="text-red-600 hover:text-red-600"
                                disabled={processing}
                                onClick={() =>
                                    submit(
                                        invoices.void(invoice.id).url,
                                        {},
                                        close,
                                    )
                                }
                            >
                                <Ban className="size-4" />
                                Batalkan
                            </Button>
                        )}
                    </div>

                    {showPay && invoice.can_pay && (
                        <div className="space-y-4 rounded-lg border p-4">
                            <div className="space-y-2">
                                <Label htmlFor="amount">Jumlah</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    min={1}
                                    max={invoice.outstanding}
                                    value={amount}
                                    onChange={(e) => setAmount(e.target.value)}
                                />
                                <InputError message={errors.amount} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="method">Metode</Label>
                                <Select value={method} onValueChange={setMethod}>
                                    <SelectTrigger id="method">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {paymentMethods.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={String(option.value)}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.method} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="reference">
                                    Referensi (opsional)
                                </Label>
                                <Input
                                    id="reference"
                                    value={reference}
                                    placeholder="No. transaksi / bukti"
                                    onChange={(e) =>
                                        setReference(e.target.value)
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="notes">Catatan (opsional)</Label>
                                <Textarea
                                    id="notes"
                                    rows={2}
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="proof">
                                    Bukti Pembayaran (opsional)
                                </Label>
                                <Input
                                    id="proof"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) =>
                                        setProof(e.target.files?.[0] ?? null)
                                    }
                                />
                                <InputError message={errors.proof} />
                            </div>
                            <Button
                                type="button"
                                disabled={processing || !amount}
                                onClick={() =>
                                    submit(
                                        invoices.pay(invoice.id).url,
                                        {
                                            amount,
                                            method,
                                            reference,
                                            notes,
                                            proof,
                                        },
                                        close,
                                    )
                                }
                            >
                                Simpan Pembayaran
                            </Button>
                        </div>
                    )}

                    {/* Payment history */}
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
                                                {payment.reference
                                                    ? ` · ${payment.reference}`
                                                    : ''}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDateTime(payment.paid_at)}
                                                {payment.recorded_by
                                                    ? ` · ${payment.recorded_by}`
                                                    : ''}
                                            </p>
                                            {payment.proof_url && (
                                                <a
                                                    href={payment.proof_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="mt-1 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                                >
                                                    <ImageIcon className="size-3" />
                                                    Lihat bukti
                                                </a>
                                            )}
                                            {payment.can_review && (
                                                <div className="mt-2 flex gap-2">
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        disabled={processing}
                                                        onClick={() =>
                                                            submit(
                                                                invoicePayments.confirm(
                                                                    payment.id,
                                                                ).url,
                                                                {},
                                                                close,
                                                            )
                                                        }
                                                    >
                                                        <Check className="size-3.5" />
                                                        Konfirmasi
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        className="text-red-600 hover:text-red-600"
                                                        disabled={processing}
                                                        onClick={() =>
                                                            submit(
                                                                invoicePayments.reject(
                                                                    payment.id,
                                                                ).url,
                                                                {},
                                                                close,
                                                            )
                                                        }
                                                    >
                                                        <X className="size-3.5" />
                                                        Tolak
                                                    </Button>
                                                </div>
                                            )}
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
