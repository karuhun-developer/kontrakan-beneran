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
import { formatRupiah } from '@/lib/format';
import bookings from '@/routes/bookings';
import type { SelectOption } from '@/types';

type PageProps = {
    roomOptions: SelectOption[];
    penyewaOptions: SelectOption[];
    statusOptions: SelectOption[];
};

export default function BookingForm({
    roomOptions,
    penyewaOptions,
    statusOptions,
}: PageProps) {
    const { data, setData, post, processing, errors } = useForm({
        room_id: '',
        penyewa_id: '',
        start_date: '',
        end_date: '',
        status: 'confirmed',
        notes: '',
    });

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
                                <Label htmlFor="start_date">Tanggal Mulai</Label>
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

                        <div className="flex items-center gap-3 pt-2">
                            <Button type="submit" disabled={processing}>
                                Simpan
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
