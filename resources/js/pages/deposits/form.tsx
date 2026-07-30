import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { ModalForm } from '@/components/master/modal-form';
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
import deposits from '@/routes/deposits';
import type { SelectOption } from '@/types/master';

type PageProps = {
    penyewaOptions: SelectOption[];
};

export default function DepositForm({ penyewaOptions }: PageProps) {
    const { data, setData, post, processing, errors } = useForm({
        penyewa_id: '',
        amount: '',
        notes: '',
    });

    return (
        <ModalForm
            title="Buka Deposit"
            description="Tahan saldo deposit untuk penyewa di cabang aktif."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();
                    post(deposits.store().url, { onSuccess: close });
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="penyewa_id">Penyewa</Label>
                            <Select
                                value={data.penyewa_id}
                                onValueChange={(value) =>
                                    setData('penyewa_id', value)
                                }
                            >
                                <SelectTrigger id="penyewa_id">
                                    <SelectValue placeholder="Pilih penyewa" />
                                </SelectTrigger>
                                <SelectContent>
                                    {penyewaOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={String(option.value)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.penyewa_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="amount">Nominal</Label>
                            <Input
                                id="amount"
                                type="number"
                                min={1}
                                value={data.amount}
                                onChange={(e) =>
                                    setData('amount', e.target.value)
                                }
                                autoFocus
                                placeholder="mis. 1000000"
                            />
                            <InputError message={errors.amount} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Catatan (opsional)</Label>
                            <Textarea
                                id="notes"
                                rows={2}
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="flex justify-end gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={close}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing || !data.penyewa_id}
                            >
                                Buka Deposit
                            </Button>
                        </div>
                    </form>
                );
            }}
        </ModalForm>
    );
}
