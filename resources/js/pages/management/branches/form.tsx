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
import branches from '@/routes/management/branches';

type Branch = {
    id: number;
    name: string;
    address: string | null;
    phone: string | null;
    bank_name: string | null;
    bank_account_number: string | null;
    bank_account_holder: string | null;
    status: string;
};

type PageProps = {
    branch?: Branch;
};

export default function BranchForm({ branch }: PageProps) {
    const isEdit = Boolean(branch);

    const { data, setData, post, put, processing, errors } = useForm({
        name: branch?.name ?? '',
        address: branch?.address ?? '',
        phone: branch?.phone ?? '',
        bank_name: branch?.bank_name ?? '',
        bank_account_number: branch?.bank_account_number ?? '',
        bank_account_holder: branch?.bank_account_holder ?? '',
        status: branch?.status ?? 'active',
    });

    return (
        <ModalForm
            title={isEdit ? 'Ubah Cabang' : 'Tambah Cabang'}
            description="Rekening di sini dipakai penyewa untuk transfer manual."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();

                    if (isEdit && branch) {
                        put(branches.update(branch.id).url, {
                            onSuccess: close,
                        });
                    } else {
                        post(branches.store().url, { onSuccess: close });
                    }
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Cabang</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                autoFocus
                                placeholder="mis. Cabang Pusat"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="address">Alamat</Label>
                            <Textarea
                                id="address"
                                value={data.address}
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                                rows={2}
                                placeholder="Opsional"
                            />
                            <InputError message={errors.address} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">Telepon</Label>
                            <Input
                                id="phone"
                                value={data.phone}
                                onChange={(e) =>
                                    setData('phone', e.target.value)
                                }
                                placeholder="Opsional"
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="space-y-4 rounded-lg border p-4">
                            <p className="text-sm font-medium">
                                Rekening Transfer
                            </p>
                            <div className="grid gap-2">
                                <Label htmlFor="bank_name">Nama Bank</Label>
                                <Input
                                    id="bank_name"
                                    value={data.bank_name}
                                    onChange={(e) =>
                                        setData('bank_name', e.target.value)
                                    }
                                    placeholder="mis. BCA"
                                />
                                <InputError message={errors.bank_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="bank_account_number">
                                    Nomor Rekening
                                </Label>
                                <Input
                                    id="bank_account_number"
                                    value={data.bank_account_number}
                                    onChange={(e) =>
                                        setData(
                                            'bank_account_number',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.bank_account_number}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="bank_account_holder">
                                    Atas Nama
                                </Label>
                                <Input
                                    id="bank_account_holder"
                                    value={data.bank_account_holder}
                                    onChange={(e) =>
                                        setData(
                                            'bank_account_holder',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.bank_account_holder}
                                />
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
