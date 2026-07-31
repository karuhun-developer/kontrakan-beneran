import { router } from '@inertiajs/react';
import { MinusCircle } from 'lucide-react';
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
import { formatDate, formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import deposits from '@/routes/deposits';
import type { DepositDetail, SelectOption } from '@/types/master';

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

export default function DepositDetailModal({
    deposit,
    transactionTypes,
}: {
    deposit: DepositDetail;
    transactionTypes: SelectOption[];
}) {
    const [showForm, setShowForm] = useState(false);
    const [type, setType] = useState(
        String(transactionTypes[0]?.value ?? 'deduction'),
    );
    const [amount, setAmount] = useState('');
    const [reason, setReason] = useState('');
    const [notes, setNotes] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const submit = (close: () => void) => {
        setProcessing(true);
        setErrors({});
        router.post(
            deposits.transaction(deposit.id).url,
            { type, amount, reason, notes },
            {
                preserveScroll: true,
                onSuccess: () => close(),
                onError: (formErrors) => setErrors(formErrors),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <ModalForm title="Deposit" description={deposit.penyewa}>
            {(close) => (
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <p className="text-lg font-semibold">
                                {deposit.penyewa}
                            </p>
                            {deposit.room && (
                                <p className="text-sm text-muted-foreground">
                                    {deposit.room}
                                </p>
                            )}
                        </div>
                        <ColorBadge
                            label={deposit.status_label}
                            color={deposit.status_color}
                        />
                    </div>

                    <div className="space-y-2 rounded-lg bg-muted/50 p-4">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">
                                Nominal Awal
                            </span>
                            <span className="font-medium">
                                {formatRupiah(deposit.amount)}
                            </span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-semibold">
                                Saldo Tersisa
                            </span>
                            <span className="text-base font-semibold">
                                {formatRupiah(deposit.balance)}
                            </span>
                        </div>
                    </div>

                    <div className="divide-y rounded-lg border px-4">
                        <InfoRow
                            label="Ditahan Sejak"
                            value={formatDate(deposit.held_at)}
                        />
                        {deposit.settled_at && (
                            <InfoRow
                                label="Selesai Pada"
                                value={formatDate(deposit.settled_at)}
                            />
                        )}
                        {deposit.notes && (
                            <InfoRow label="Catatan" value={deposit.notes} />
                        )}
                    </div>

                    {deposit.can_transact && (
                        <Button
                            type="button"
                            onClick={() => setShowForm((v) => !v)}
                        >
                            <MinusCircle className="size-4" />
                            Potong / Kembalikan
                        </Button>
                    )}

                    {showForm && deposit.can_transact && (
                        <div className="space-y-4 rounded-lg border p-4">
                            <div className="space-y-2">
                                <Label htmlFor="type">Jenis</Label>
                                <Select value={type} onValueChange={setType}>
                                    <SelectTrigger id="type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {transactionTypes.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={String(option.value)}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="amount">Nominal</Label>
                                <Input
                                    id="amount"
                                    type="number"
                                    min={1}
                                    max={deposit.balance}
                                    value={amount}
                                    onChange={(e) => setAmount(e.target.value)}
                                />
                                <InputError message={errors.amount} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="reason">
                                    Alasan (opsional)
                                </Label>
                                <Input
                                    id="reason"
                                    value={reason}
                                    placeholder="mis. Ganti rugi, Sisa dikembalikan"
                                    onChange={(e) => setReason(e.target.value)}
                                />
                                <InputError message={errors.reason} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="notes">
                                    Catatan (opsional)
                                </Label>
                                <Textarea
                                    id="notes"
                                    rows={2}
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                />
                            </div>
                            <Button
                                type="button"
                                disabled={processing || !amount}
                                onClick={() => submit(close)}
                            >
                                Simpan Transaksi
                            </Button>
                        </div>
                    )}

                    {/* Ledger */}
                    <div>
                        <p className="mb-3 text-sm font-medium">
                            Riwayat Ledger
                        </p>
                        {deposit.transactions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Belum ada transaksi.
                            </p>
                        ) : (
                            <ol className="space-y-3">
                                {deposit.transactions.map((transaction) => (
                                    <li
                                        key={transaction.id}
                                        className="flex items-start justify-between gap-4 rounded-lg border px-4 py-2.5 text-sm"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {transaction.reason ??
                                                    transaction.type_label}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDate(
                                                    transaction.occurred_on,
                                                )}
                                                {transaction.recorded_by
                                                    ? ` · ${transaction.recorded_by}`
                                                    : ''}
                                            </p>
                                        </div>
                                        <div className="flex flex-col items-end gap-1">
                                            <span
                                                className={cn(
                                                    'font-medium',
                                                    transaction.sign > 0
                                                        ? 'text-green-600 dark:text-green-400'
                                                        : 'text-red-600 dark:text-red-400',
                                                )}
                                            >
                                                {transaction.sign > 0
                                                    ? '+'
                                                    : '−'}
                                                {formatRupiah(
                                                    transaction.amount,
                                                )}
                                            </span>
                                            <ColorBadge
                                                label={transaction.type_label}
                                                color={transaction.type_color}
                                            />
                                        </div>
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
