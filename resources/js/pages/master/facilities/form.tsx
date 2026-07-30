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
import facilities from '@/routes/facilities';

type Facility = {
    id: number;
    name: string;
    description: string | null;
    status: string;
};

type PageProps = {
    facility?: Facility;
};

export default function FacilityForm({ facility }: PageProps) {
    const isEdit = Boolean(facility);

    const { data, setData, post, put, processing, errors } = useForm({
        name: facility?.name ?? '',
        description: facility?.description ?? '',
        status: facility?.status ?? 'active',
    });

    return (
        <ModalForm
            title={isEdit ? 'Ubah Fasilitas' : 'Tambah Fasilitas'}
            description="Fasilitas dapat dilekatkan ke satu atau banyak kamar."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();

                    if (isEdit && facility) {
                        put(facilities.update(facility.id).url, {
                            onSuccess: close,
                        });
                    } else {
                        post(facilities.store().url, { onSuccess: close });
                    }
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Fasilitas</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                autoFocus
                                placeholder="mis. AC, WiFi, Kamar Mandi Dalam"
                            />
                            <InputError message={errors.name} />
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
