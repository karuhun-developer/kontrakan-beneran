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
import laundry from '@/routes/laundry';
import type { SelectOption } from '@/types/master';

type PageProps = {
    penyewaOptions: SelectOption[];
    paymentSources: SelectOption[];
};

export default function LaundryForm({
    penyewaOptions,
    paymentSources,
}: PageProps) {
    const { data, setData, post, processing, errors } = useForm({
        penyewa_id: '',
        payment_source: String(paymentSources[0]?.value ?? 'invoice'),
        weight: '',
        price: '',
        notes: '',
    });

    return (
        <ModalForm
            title="Order Laundry"
            description="Catat order laundry dan pilih cara pembayarannya."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();
                    post(laundry.store().url, { onSuccess: close });
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

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="weight">
                                    Berat / kg (opsional)
                                </Label>
                                <Input
                                    id="weight"
                                    type="number"
                                    min={0.1}
                                    step={0.1}
                                    value={data.weight}
                                    onChange={(e) =>
                                        setData('weight', e.target.value)
                                    }
                                    placeholder="mis. 5"
                                />
                                <InputError message={errors.weight} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="price">Harga</Label>
                                <Input
                                    id="price"
                                    type="number"
                                    min={1}
                                    value={data.price}
                                    onChange={(e) =>
                                        setData('price', e.target.value)
                                    }
                                    placeholder="mis. 35000"
                                />
                                <InputError message={errors.price} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="payment_source">
                                Sumber Pembayaran
                            </Label>
                            <Select
                                value={data.payment_source}
                                onValueChange={(value) =>
                                    setData('payment_source', value)
                                }
                            >
                                <SelectTrigger id="payment_source">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {paymentSources.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={String(option.value)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.payment_source} />
                            <p className="text-xs text-muted-foreground">
                                Potong deposit langsung mengurangi saldo penyewa;
                                masuk tagihan membuat tagihan baru.
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Catatan (opsional)</Label>
                            <Textarea
                                id="notes"
                                rows={2}
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
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
                                disabled={
                                    processing || !data.penyewa_id || !data.price
                                }
                            >
                                Simpan Order
                            </Button>
                        </div>
                    </form>
                );
            }}
        </ModalForm>
    );
}
