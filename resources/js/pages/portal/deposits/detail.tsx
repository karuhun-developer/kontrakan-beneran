import { ModalForm } from '@/components/master/modal-form';
import { ColorBadge } from '@/components/master/status-badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { PortalDepositDetail } from '@/types/portal';

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2 text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-medium">{value}</span>
        </div>
    );
}

export default function PortalDepositDetailModal({
    deposit,
}: {
    deposit: PortalDepositDetail;
}) {
    return (
        <ModalForm title="Detail Deposit">
            {(close) => (
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <p className="text-lg font-semibold">
                                {formatRupiah(deposit.balance)}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Saldo saat ini
                            </p>
                        </div>
                        <ColorBadge
                            label={deposit.status_label}
                            color={deposit.status_color}
                        />
                    </div>

                    <div className="divide-y rounded-lg border px-4">
                        <InfoRow
                            label="Nominal Ditahan"
                            value={formatRupiah(deposit.amount)}
                        />
                        {deposit.held_at && (
                            <InfoRow
                                label="Tanggal Ditahan"
                                value={formatDate(deposit.held_at)}
                            />
                        )}
                        {deposit.settled_at && (
                            <InfoRow
                                label="Diselesaikan"
                                value={formatDate(deposit.settled_at)}
                            />
                        )}
                        {deposit.notes && (
                            <InfoRow label="Catatan" value={deposit.notes} />
                        )}
                    </div>

                    <div>
                        <p className="mb-3 text-sm font-medium">
                            Riwayat Transaksi
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
                                            <div className="flex items-center gap-2">
                                                <ColorBadge
                                                    label={
                                                        transaction.type_label
                                                    }
                                                    color={
                                                        transaction.type_color
                                                    }
                                                />
                                            </div>
                                            {transaction.reason && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {transaction.reason}
                                                </p>
                                            )}
                                            {transaction.occurred_on && (
                                                <p className="text-xs text-muted-foreground">
                                                    {formatDate(
                                                        transaction.occurred_on,
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                        <span
                                            className={cn(
                                                'font-medium whitespace-nowrap',
                                                transaction.sign < 0
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : 'text-green-600 dark:text-green-400',
                                            )}
                                        >
                                            {transaction.sign < 0 ? '- ' : '+ '}
                                            {formatRupiah(transaction.amount)}
                                        </span>
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
