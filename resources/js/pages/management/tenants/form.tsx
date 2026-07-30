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
import tenants from '@/routes/management/tenants';

type Tenant = {
    id: number;
    name: string;
    description: string | null;
    status: string;
};

type PageProps = {
    tenant?: Tenant;
};

export default function TenantForm({ tenant }: PageProps) {
    const isEdit = Boolean(tenant);

    const { data, setData, post, put, processing, errors } = useForm({
        name: tenant?.name ?? '',
        description: tenant?.description ?? '',
        status: tenant?.status ?? 'active',
        owner_name: '',
        owner_email: '',
        owner_password: '',
    });

    return (
        <ModalForm
            title={isEdit ? 'Ubah Bisnis' : 'Tambah Bisnis'}
            description={
                isEdit
                    ? 'Perbarui profil bisnis.'
                    : 'Buat bisnis baru sekaligus akun owner-nya.'
            }
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();

                    if (isEdit && tenant) {
                        put(tenants.update(tenant.id).url, {
                            onSuccess: close,
                        });
                    } else {
                        post(tenants.store().url, { onSuccess: close });
                    }
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Bisnis</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                autoFocus
                                placeholder="mis. Kost Melati Group"
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
                                rows={2}
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

                        {!isEdit && (
                            <div className="space-y-4 rounded-lg border p-4">
                                <p className="text-sm font-medium">
                                    Akun Owner
                                </p>
                                <div className="grid gap-2">
                                    <Label htmlFor="owner_name">
                                        Nama Owner
                                    </Label>
                                    <Input
                                        id="owner_name"
                                        value={data.owner_name}
                                        onChange={(e) =>
                                            setData(
                                                'owner_name',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.owner_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="owner_email">
                                        Email Owner
                                    </Label>
                                    <Input
                                        id="owner_email"
                                        type="email"
                                        value={data.owner_email}
                                        onChange={(e) =>
                                            setData(
                                                'owner_email',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.owner_email} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="owner_password">
                                        Password Owner
                                    </Label>
                                    <Input
                                        id="owner_password"
                                        type="password"
                                        value={data.owner_password}
                                        onChange={(e) =>
                                            setData(
                                                'owner_password',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Minimal 8 karakter"
                                    />
                                    <InputError
                                        message={errors.owner_password}
                                    />
                                </div>
                            </div>
                        )}

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
