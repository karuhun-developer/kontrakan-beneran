import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
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
import roomTypes from '@/routes/room-types';

type RoomType = {
    id: number;
    name: string;
    description: string | null;
    capacity: number;
    monthly_price: number;
    status: string;
};

type PageProps = {
    roomType?: RoomType;
};

export default function RoomTypeForm({ roomType }: PageProps) {
    const isEdit = Boolean(roomType);

    const { data, setData, post, put, processing, errors } = useForm({
        name: roomType?.name ?? '',
        description: roomType?.description ?? '',
        capacity: roomType?.capacity ?? 1,
        monthly_price: roomType?.monthly_price ?? 0,
        status: roomType?.status ?? 'active',
    });

    return (
        <ModalForm
            title={isEdit ? 'Ubah Tipe Kamar' : 'Tambah Tipe Kamar'}
            description="Tipe kamar menetapkan kapasitas dan harga dasar."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();

                    if (isEdit && roomType) {
                        put(roomTypes.update(roomType.id).url, {
                            onSuccess: close,
                        });
                    } else {
                        post(roomTypes.store().url, { onSuccess: close });
                    }
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Tipe</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                autoFocus
                                placeholder="mis. Standard, Deluxe"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="capacity">
                                    Kapasitas (orang)
                                </Label>
                                <Input
                                    id="capacity"
                                    type="number"
                                    min={1}
                                    max={20}
                                    value={data.capacity}
                                    onChange={(e) =>
                                        setData(
                                            'capacity',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                                <InputError message={errors.capacity} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="monthly_price">
                                    Harga / Bulan (Rp)
                                </Label>
                                <Input
                                    id="monthly_price"
                                    type="number"
                                    min={0}
                                    value={data.monthly_price}
                                    onChange={(e) =>
                                        setData(
                                            'monthly_price',
                                            Number(e.target.value),
                                        )
                                    }
                                />
                                <InputError message={errors.monthly_price} />
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
