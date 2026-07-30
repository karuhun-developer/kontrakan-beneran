import { useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { ChangeEvent, FormEvent } from 'react';
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
import penyewa from '@/routes/penyewa';

type Penyewa = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    id_card_number: string | null;
    gender: string | null;
    address: string | null;
    status: string;
    id_card_url: string | null;
    has_account?: boolean;
    account_email?: string | null;
};

type PageProps = {
    penyewa?: Penyewa;
};

export default function PenyewaForm({ penyewa: model }: PageProps) {
    const isEdit = Boolean(model);

    const { data, setData, post, transform, processing, errors } = useForm({
        name: model?.name ?? '',
        email: model?.email ?? '',
        phone: model?.phone ?? '',
        id_card_number: model?.id_card_number ?? '',
        gender: model?.gender ?? '',
        address: model?.address ?? '',
        status: model?.status ?? 'active',
        id_card: null as File | null,
        deposit_amount: '',
        password: '',
    });

    const account = useForm({
        email: model?.account_email ?? model?.email ?? '',
        password: '',
    });

    const handleFile = (event: ChangeEvent<HTMLInputElement>) => {
        setData('id_card', event.target.files?.[0] ?? null);
    };

    return (
        <ModalForm
            title={isEdit ? 'Ubah Penyewa' : 'Tambah Penyewa'}
            description="Data identitas penyewa dipakai untuk booking dan tagihan."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();

                    if (isEdit && model) {
                        transform((payload) => ({
                            ...payload,
                            _method: 'put',
                        }));
                        post(penyewa.update(model.id).url, {
                            forceFormData: true,
                            onSuccess: close,
                        });
                    } else {
                        post(penyewa.store().url, {
                            forceFormData: true,
                            onSuccess: close,
                        });
                    }
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Lengkap</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                autoFocus
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="phone">No. HP</Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData('phone', e.target.value)
                                    }
                                    placeholder="08xxxxxxxxxx"
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        {!isEdit && data.email.trim() !== '' && (
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    Kata Sandi Portal (opsional)
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    placeholder="Minimal 8 karakter"
                                    autoComplete="new-password"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Karena email diisi, penyewa otomatis dapat
                                    akun portal. Kosongkan untuk memakai kata
                                    sandi default.
                                </p>
                                <InputError message={errors.password} />
                            </div>
                        )}

                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="id_card_number">No. KTP</Label>
                                <Input
                                    id="id_card_number"
                                    value={data.id_card_number}
                                    onChange={(e) =>
                                        setData(
                                            'id_card_number',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.id_card_number} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="gender">Gender</Label>
                                <Select
                                    value={data.gender}
                                    onValueChange={(value) =>
                                        setData('gender', value)
                                    }
                                >
                                    <SelectTrigger id="gender">
                                        <SelectValue placeholder="Pilih gender" />
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
                                <InputError message={errors.gender} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="address">Alamat</Label>
                            <Textarea
                                id="address"
                                value={data.address}
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                                rows={3}
                            />
                            <InputError message={errors.address} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="id_card">Foto KTP</Label>
                            {model?.id_card_url && (
                                <img
                                    src={model.id_card_url}
                                    alt="KTP saat ini"
                                    className="h-32 w-auto rounded-md border object-cover"
                                />
                            )}
                            <Input
                                id="id_card"
                                type="file"
                                accept="image/*"
                                onChange={handleFile}
                            />
                            <InputError message={errors.id_card} />
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
                            <div className="grid gap-2 rounded-lg border border-dashed p-4">
                                <Label htmlFor="deposit_amount">
                                    Deposit Awal (opsional)
                                </Label>
                                <Input
                                    id="deposit_amount"
                                    type="number"
                                    min={1}
                                    value={data.deposit_amount}
                                    onChange={(e) =>
                                        setData(
                                            'deposit_amount',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="mis. 1000000"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Isi untuk langsung menahan deposit saat
                                    penyewa dibuat. Kosongkan bila belum ada.
                                </p>
                                <InputError message={errors.deposit_amount} />
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

                        {isEdit && model && (
                            <div className="space-y-4 rounded-lg border p-4">
                                <div className="flex items-center gap-2">
                                    <KeyRound className="size-4 text-muted-foreground" />
                                    <p className="text-sm font-medium">
                                        Akun Portal
                                    </p>
                                    {model.has_account && (
                                        <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                            Aktif
                                        </span>
                                    )}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {model.has_account
                                        ? 'Setel ulang email/kata sandi login penyewa.'
                                        : 'Buat kredensial agar penyewa bisa login ke portal.'}
                                </p>
                                <div className="grid gap-2">
                                    <Label htmlFor="account_email">Email</Label>
                                    <Input
                                        id="account_email"
                                        type="email"
                                        value={account.data.email}
                                        onChange={(e) =>
                                            account.setData(
                                                'email',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={account.errors.email}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="account_password">
                                        Kata Sandi
                                    </Label>
                                    <Input
                                        id="account_password"
                                        type="password"
                                        value={account.data.password}
                                        onChange={(e) =>
                                            account.setData(
                                                'password',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Minimal 8 karakter"
                                        autoComplete="new-password"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Kosongkan untuk memakai kata sandi
                                        default.
                                    </p>
                                    <InputError
                                        message={account.errors.password}
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    disabled={account.processing}
                                    onClick={() =>
                                        account.post(
                                            penyewa.account(model.id).url,
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {model.has_account
                                        ? 'Simpan Akun'
                                        : 'Buat Akun Portal'}
                                </Button>
                            </div>
                        )}
                    </form>
                );
            }}
        </ModalForm>
    );
}
