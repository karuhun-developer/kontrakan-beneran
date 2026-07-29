import { useForm } from '@inertiajs/react';
import { FormEvent, useMemo } from 'react';
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
import cashflows from '@/routes/cashflows';
import type {
    CashflowCategoryOption,
    SelectOption,
} from '@/types/master';

type Cashflow = {
    id: number;
    type: string;
    amount: number;
    description: string;
    occurred_on: string;
    cashflow_category_id: number | null;
    notes: string | null;
};

type PageProps = {
    cashflow?: Cashflow;
    categories: CashflowCategoryOption[];
    typeOptions: SelectOption[];
};

const NO_CATEGORY = 'none';

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

export default function CashflowForm({
    cashflow,
    categories,
    typeOptions,
}: PageProps) {
    const isEdit = Boolean(cashflow);

    const { data, setData, post, put, processing, errors, transform } =
        useForm({
            type: cashflow?.type ?? String(typeOptions[0]?.value ?? 'expense'),
            amount: cashflow ? String(cashflow.amount) : '',
            description: cashflow?.description ?? '',
            occurred_on: cashflow?.occurred_on ?? today(),
            cashflow_category_id: cashflow?.cashflow_category_id
                ? String(cashflow.cashflow_category_id)
                : NO_CATEGORY,
            notes: cashflow?.notes ?? '',
        });

    transform((payload) => ({
        ...payload,
        cashflow_category_id:
            payload.cashflow_category_id === NO_CATEGORY
                ? null
                : payload.cashflow_category_id,
    }));

    const availableCategories = useMemo(
        () => categories.filter((category) => category.type === data.type),
        [categories, data.type],
    );

    const handleTypeChange = (value: string) => {
        setData((previous) => ({
            ...previous,
            type: value,
            cashflow_category_id: NO_CATEGORY,
        }));
    };

    return (
        <ModalForm
            title={isEdit ? 'Ubah Transaksi Kas' : 'Catat Transaksi Kas'}
            description="Pemasukan atau pengeluaran manual untuk cabang aktif."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();

                    if (isEdit && cashflow) {
                        put(cashflows.update(cashflow.id).url, {
                            onSuccess: close,
                        });
                    } else {
                        post(cashflows.store().url, { onSuccess: close });
                    }
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="type">Tipe</Label>
                            <Select
                                value={data.type}
                                onValueChange={handleTypeChange}
                            >
                                <SelectTrigger id="type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {typeOptions.map((option) => (
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

                        <div className="grid gap-2">
                            <Label htmlFor="amount">Jumlah</Label>
                            <Input
                                id="amount"
                                type="number"
                                min={1}
                                value={data.amount}
                                onChange={(e) =>
                                    setData('amount', e.target.value)
                                }
                                autoFocus
                                placeholder="mis. 150000"
                            />
                            <InputError message={errors.amount} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                placeholder="mis. Bayar listrik, Beli galon"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="occurred_on">Tanggal</Label>
                            <Input
                                id="occurred_on"
                                type="date"
                                value={data.occurred_on}
                                onChange={(e) =>
                                    setData('occurred_on', e.target.value)
                                }
                            />
                            <InputError message={errors.occurred_on} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="cashflow_category_id">
                                Kategori
                            </Label>
                            <Select
                                value={data.cashflow_category_id}
                                onValueChange={(value) =>
                                    setData('cashflow_category_id', value)
                                }
                            >
                                <SelectTrigger id="cashflow_category_id">
                                    <SelectValue placeholder="Tanpa kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_CATEGORY}>
                                        Tanpa kategori
                                    </SelectItem>
                                    {availableCategories.map((category) => (
                                        <SelectItem
                                            key={category.value}
                                            value={String(category.value)}
                                        >
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.cashflow_category_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Catatan</Label>
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                rows={3}
                                placeholder="Opsional"
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="flex items-center gap-3 pt-2">
                            <Button type="submit" disabled={processing}>
                                {isEdit ? 'Simpan Perubahan' : 'Simpan'}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={close}
                            >
                                Batal
                            </Button>
                        </div>
                    </form>
                );
            }}
        </ModalForm>
    );
}
