import { useForm } from '@inertiajs/react';
import { UserPlus, X } from 'lucide-react';
import { FormEvent, useState } from 'react';
import InputError from '@/components/input-error';
import { ModalForm } from '@/components/master/modal-form';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { cn } from '@/lib/utils';
import { formatRupiah } from '@/lib/format';
import { HttpError, postJson } from '@/lib/http';
import bookings from '@/routes/bookings';
import penyewa from '@/routes/penyewa';
import type { SelectOption } from '@/types';

type PageProps = {
    roomOptions: SelectOption[];
    penyewaOptions: SelectOption[];
    statusOptions: SelectOption[];
    paymentMethods: SelectOption[];
};

type Mode = 'reserve' | 'walkin';

/** Local (not UTC) today as YYYY-MM-DD for date input defaults. */
function todayIso(): string {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 10);
}

export default function BookingForm({
    roomOptions,
    penyewaOptions,
    statusOptions,
    paymentMethods,
}: PageProps) {
    const { data, setData, post, processing, errors } = useForm({
        room_id: '',
        penyewa_id: '',
        start_date: '',
        end_date: '',
        status: 'confirmed',
        notes: '',
        generate_invoice: true as boolean,
        pay_now: false as boolean,
        pay_amount: '',
        pay_method: String(paymentMethods[0]?.value ?? 'cash'),
    });

    const [mode, setMode] = useState<Mode>('reserve');
    const [options, setOptions] = useState<SelectOption[]>(penyewaOptions);
    const [showQuickCreate, setShowQuickCreate] = useState(false);
    const [quick, setQuick] = useState({
        name: '',
        phone: '',
        gender: '',
        deposit_amount: '',
    });
    const [quickErrors, setQuickErrors] = useState<Record<string, string>>({});
    const [savingPenyewa, setSavingPenyewa] = useState(false);

    const saveQuickPenyewa = async () => {
        setSavingPenyewa(true);
        setQuickErrors({});
        try {
            const created = await postJson<{ id: number; name: string }>(
                penyewa.quickStore().url,
                {
                    name: quick.name,
                    phone: quick.phone || null,
                    gender: quick.gender || null,
                    deposit_amount: quick.deposit_amount
                        ? Number(quick.deposit_amount)
                        : null,
                },
            );
            setOptions((prev) => [
                { value: created.id, label: created.name },
                ...prev,
            ]);
            setData('penyewa_id', String(created.id));
            setQuick({ name: '', phone: '', gender: '', deposit_amount: '' });
            setShowQuickCreate(false);
        } catch (error) {
            if (error instanceof HttpError && error.status === 422) {
                setQuickErrors(error.errors);
            } else {
                setQuickErrors({ name: 'Gagal menyimpan penyewa.' });
            }
        } finally {
            setSavingPenyewa(false);
        }
    };

    const switchMode = (next: Mode) => {
        setMode(next);
        if (next === 'walkin') {
            // Walk-in = check-in on the spot: start today, force checked-in.
            setData((prev) => ({
                ...prev,
                status: 'checked_in',
                start_date: prev.start_date || todayIso(),
            }));
        } else {
            // Inline "pay now" is a walk-in affordance only.
            setData((prev) => ({
                ...prev,
                status: 'confirmed',
                pay_now: false,
            }));
        }
    };

    const selectedRoom = roomOptions.find(
        (option) => String(option.value) === data.room_id,
    );

    return (
        <ModalForm
            title="Buat Booking"
            description="Cek ketersediaan kamar otomatis untuk rentang tanggal yang dipilih."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();
                    post(bookings.store().url, { onSuccess: close });
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid grid-cols-2 gap-1 rounded-lg bg-muted p-1">
                            {(
                                [
                                    ['reserve', 'Reservasi'],
                                    ['walkin', 'Walk-in'],
                                ] as [Mode, string][]
                            ).map(([value, label]) => (
                                <button
                                    key={value}
                                    type="button"
                                    onClick={() => switchMode(value)}
                                    className={cn(
                                        'rounded-md px-3 py-1.5 text-sm font-medium transition',
                                        mode === value
                                            ? 'bg-background text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                        {mode === 'walkin' && (
                            <p className="-mt-3 text-xs text-muted-foreground">
                                Penyewa langsung check-in hari ini. Kamar akan
                                ditandai terisi.
                            </p>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="room_id">Kamar</Label>
                            <Select
                                value={data.room_id}
                                onValueChange={(value) =>
                                    setData('room_id', value)
                                }
                            >
                                <SelectTrigger id="room_id">
                                    <SelectValue placeholder="Pilih kamar" />
                                </SelectTrigger>
                                <SelectContent>
                                    {roomOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={String(option.value)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {selectedRoom?.monthly_price != null && (
                                <p className="text-sm text-muted-foreground">
                                    Harga:{' '}
                                    {formatRupiah(selectedRoom.monthly_price)} /
                                    bulan
                                </p>
                            )}
                            <InputError message={errors.room_id} />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="penyewa_id">Penyewa</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 gap-1.5 px-2 text-xs"
                                    onClick={() =>
                                        setShowQuickCreate((prev) => !prev)
                                    }
                                >
                                    {showQuickCreate ? (
                                        <X className="size-3.5" />
                                    ) : (
                                        <UserPlus className="size-3.5" />
                                    )}
                                    {showQuickCreate
                                        ? 'Tutup'
                                        : 'Penyewa baru'}
                                </Button>
                            </div>
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
                                    {options.map((option) => (
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

                            {showQuickCreate && (
                                <div className="mt-1 space-y-3 rounded-lg border bg-muted/30 p-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="quick_name">
                                            Nama Penyewa
                                        </Label>
                                        <Input
                                            id="quick_name"
                                            value={quick.name}
                                            onChange={(e) =>
                                                setQuick((prev) => ({
                                                    ...prev,
                                                    name: e.target.value,
                                                }))
                                            }
                                            placeholder="Nama lengkap"
                                        />
                                        <InputError
                                            message={quickErrors.name}
                                        />
                                    </div>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="quick_phone">
                                                No. HP
                                            </Label>
                                            <Input
                                                id="quick_phone"
                                                value={quick.phone}
                                                onChange={(e) =>
                                                    setQuick((prev) => ({
                                                        ...prev,
                                                        phone: e.target.value,
                                                    }))
                                                }
                                                placeholder="Opsional"
                                            />
                                            <InputError
                                                message={quickErrors.phone}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="quick_gender">
                                                Gender
                                            </Label>
                                            <Select
                                                value={quick.gender}
                                                onValueChange={(value) =>
                                                    setQuick((prev) => ({
                                                        ...prev,
                                                        gender: value,
                                                    }))
                                                }
                                            >
                                                <SelectTrigger id="quick_gender">
                                                    <SelectValue placeholder="Pilih" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="male">
                                                        Laki-laki
                                                    </SelectItem>
                                                    <SelectItem value="female">
                                                        Perempuan
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={quickErrors.gender}
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="quick_deposit">
                                            Deposit Awal (opsional)
                                        </Label>
                                        <Input
                                            id="quick_deposit"
                                            type="number"
                                            min={1}
                                            value={quick.deposit_amount}
                                            onChange={(e) =>
                                                setQuick((prev) => ({
                                                    ...prev,
                                                    deposit_amount:
                                                        e.target.value,
                                                }))
                                            }
                                            placeholder="mis. 1000000"
                                        />
                                        <InputError
                                            message={quickErrors.deposit_amount}
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        size="sm"
                                        disabled={
                                            savingPenyewa || !quick.name.trim()
                                        }
                                        onClick={saveQuickPenyewa}
                                    >
                                        Simpan & Pilih
                                    </Button>
                                </div>
                            )}
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="start_date">
                                    {mode === 'walkin'
                                        ? 'Tanggal Masuk'
                                        : 'Tanggal Mulai'}
                                </Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={data.start_date}
                                    onChange={(e) =>
                                        setData('start_date', e.target.value)
                                    }
                                />
                                <InputError message={errors.start_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="end_date">Tanggal Selesai</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={data.end_date}
                                    onChange={(e) =>
                                        setData('end_date', e.target.value)
                                    }
                                />
                                <InputError message={errors.end_date} />
                            </div>
                        </div>

                        {mode === 'reserve' && (
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData('status', value)
                                    }
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statusOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={String(option.value)}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Catatan</Label>
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                rows={3}
                                placeholder="Opsional"
                            />
                            <InputError message={errors.notes} />
                        </div>

                        {/* Tagihan & pembayaran */}
                        <div className="space-y-3 rounded-lg border p-4">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="generate_invoice"
                                    className="mt-0.5"
                                    checked={data.generate_invoice}
                                    onCheckedChange={(value) => {
                                        const checked = value === true;
                                        setData((prev) => ({
                                            ...prev,
                                            generate_invoice: checked,
                                            pay_now: checked
                                                ? prev.pay_now
                                                : false,
                                        }));
                                    }}
                                />
                                <div className="space-y-0.5">
                                    <Label
                                        htmlFor="generate_invoice"
                                        className="cursor-pointer"
                                    >
                                        Bikin tagihan bulan ini
                                    </Label>
                                    {selectedRoom?.monthly_price != null && (
                                        <p className="text-xs text-muted-foreground">
                                            Tagihan:{' '}
                                            {formatRupiah(
                                                selectedRoom.monthly_price,
                                            )}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {mode === 'walkin' && data.generate_invoice && (
                                <div className="space-y-3 border-t pt-3">
                                    <div className="flex items-center gap-3">
                                        <Checkbox
                                            id="pay_now"
                                            checked={data.pay_now}
                                            onCheckedChange={(value) => {
                                                const checked = value === true;
                                                setData((prev) => ({
                                                    ...prev,
                                                    pay_now: checked,
                                                    pay_amount:
                                                        checked &&
                                                        !prev.pay_amount
                                                            ? String(
                                                                  selectedRoom?.monthly_price ??
                                                                      '',
                                                              )
                                                            : prev.pay_amount,
                                                }));
                                            }}
                                        />
                                        <Label
                                            htmlFor="pay_now"
                                            className="cursor-pointer"
                                        >
                                            Bayar sekarang
                                        </Label>
                                    </div>

                                    {data.pay_now && (
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="pay_amount">
                                                    Jumlah
                                                </Label>
                                                <Input
                                                    id="pay_amount"
                                                    type="number"
                                                    min={1}
                                                    max={
                                                        selectedRoom?.monthly_price ??
                                                        undefined
                                                    }
                                                    value={data.pay_amount}
                                                    onChange={(e) =>
                                                        setData(
                                                            'pay_amount',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={errors.pay_amount}
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="pay_method">
                                                    Metode
                                                </Label>
                                                <Select
                                                    value={data.pay_method}
                                                    onValueChange={(value) =>
                                                        setData(
                                                            'pay_method',
                                                            value,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger id="pay_method">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {paymentMethods.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={String(
                                                                        option.value,
                                                                    )}
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={errors.pay_method}
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="flex items-center gap-3 pt-2">
                            <Button type="submit" disabled={processing}>
                                {mode === 'walkin'
                                    ? data.pay_now
                                        ? 'Check-in & Bayar'
                                        : 'Check-in Sekarang'
                                    : 'Simpan'}
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
