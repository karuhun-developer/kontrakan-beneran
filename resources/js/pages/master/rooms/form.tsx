import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
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
import { formatRupiah } from '@/lib/format';
import rooms from '@/routes/rooms';
import type { SelectOption } from '@/types';

type Room = {
    id: number;
    room_type_id: number;
    number: string;
    name: string | null;
    floor: number;
    description: string | null;
    monthly_price: number | null;
    status: string;
    room_status: string;
    facilities: number[];
};

type PageProps = {
    room?: Room;
    roomTypeOptions: SelectOption[];
    facilityOptions: SelectOption[];
    roomStatusOptions: SelectOption[];
};

export default function RoomForm({
    room,
    roomTypeOptions,
    facilityOptions,
    roomStatusOptions,
}: PageProps) {
    const isEdit = Boolean(room);

    const { data, setData, post, put, processing, errors } = useForm({
        room_type_id: room?.room_type_id ? String(room.room_type_id) : '',
        number: room?.number ?? '',
        name: room?.name ?? '',
        floor: room?.floor ?? 1,
        description: room?.description ?? '',
        monthly_price: room?.monthly_price ?? ('' as number | ''),
        status: room?.status ?? 'active',
        room_status: room?.room_status ?? 'available',
        facilities: room?.facilities ?? ([] as number[]),
    });

    const selectedType = roomTypeOptions.find(
        (option) => String(option.value) === data.room_type_id,
    );

    const toggleFacility = (id: number, checked: boolean) => {
        setData(
            'facilities',
            checked
                ? [...data.facilities, id]
                : data.facilities.filter((value) => value !== id),
        );
    };

    return (
        <ModalForm
            title={isEdit ? 'Ubah Kamar' : 'Tambah Kamar'}
            description="Kamar mengikuti harga tipe kecuali harga khusus diisi."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();

                    if (isEdit && room) {
                        put(rooms.update(room.id).url, { onSuccess: close });
                    } else {
                        post(rooms.store().url, { onSuccess: close });
                    }
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="room_type_id">Tipe Kamar</Label>
                            <Select
                                value={data.room_type_id}
                                onValueChange={(value) =>
                                    setData('room_type_id', value)
                                }
                            >
                                <SelectTrigger id="room_type_id">
                                    <SelectValue placeholder="Pilih tipe kamar" />
                                </SelectTrigger>
                                <SelectContent>
                                    {roomTypeOptions.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={String(option.value)}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.room_type_id} />
                        </div>

                        <div className="grid gap-2 sm:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="number">Nomor Kamar</Label>
                                <Input
                                    id="number"
                                    value={data.number}
                                    onChange={(e) =>
                                        setData('number', e.target.value)
                                    }
                                    autoFocus
                                    placeholder="mis. A-101"
                                />
                                <InputError message={errors.number} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama (opsional)</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="floor">Lantai</Label>
                                <Input
                                    id="floor"
                                    type="number"
                                    min={1}
                                    max={100}
                                    value={data.floor}
                                    onChange={(e) =>
                                        setData('floor', Number(e.target.value))
                                    }
                                />
                                <InputError message={errors.floor} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="monthly_price">
                                Harga Khusus / Bulan (Rp)
                            </Label>
                            <Input
                                id="monthly_price"
                                type="number"
                                min={0}
                                value={data.monthly_price}
                                onChange={(e) =>
                                    setData(
                                        'monthly_price',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                                placeholder={
                                    selectedType?.monthly_price != null
                                        ? `Default tipe: ${formatRupiah(selectedType.monthly_price)}`
                                        : 'Kosongkan untuk ikut harga tipe'
                                }
                            />
                            <InputError message={errors.monthly_price} />
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="room_status">
                                    Status Hunian
                                </Label>
                                <Select
                                    value={data.room_status}
                                    onValueChange={(value) =>
                                        setData('room_status', value)
                                    }
                                >
                                    <SelectTrigger id="room_status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roomStatusOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={String(option.value)}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.room_status} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status Data</Label>
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
                                        <SelectItem value="active">
                                            Aktif
                                        </SelectItem>
                                        <SelectItem value="inactive">
                                            Nonaktif
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                rows={3}
                                placeholder="Opsional"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="grid gap-3">
                            <Label>Fasilitas</Label>
                            {facilityOptions.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada fasilitas di cabang ini.
                                </p>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {facilityOptions.map((option) => {
                                        const id = Number(option.value);
                                        const checked =
                                            data.facilities.includes(id);

                                        return (
                                            <label
                                                key={option.value}
                                                className="flex items-center gap-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={checked}
                                                    onCheckedChange={(value) =>
                                                        toggleFacility(
                                                            id,
                                                            value === true,
                                                        )
                                                    }
                                                />
                                                {option.label}
                                            </label>
                                        );
                                    })}
                                </div>
                            )}
                            <InputError message={errors.facilities} />
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
