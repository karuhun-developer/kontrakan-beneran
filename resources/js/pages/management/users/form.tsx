import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { ModalForm } from '@/components/master/modal-form';
import { Badge } from '@/components/ui/badge';
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
import users from '@/routes/management/users';
import type { BranchSelectOption, ManagementSelectOption } from '@/types';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: string | null;
    role_label: string | null;
    is_owner: boolean;
    branches: number[];
};

type PageProps = {
    user?: ManagedUser;
    roleOptions: ManagementSelectOption[];
    branchOptions: BranchSelectOption[];
};

export default function UserForm({
    user,
    roleOptions,
    branchOptions,
}: PageProps) {
    const isEdit = Boolean(user);
    const isOwner = Boolean(user?.is_owner);

    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        role: user?.role ?? roleOptions[0]?.value ?? '',
        branches: user?.branches ?? [],
    });

    const toggleBranch = (id: number) => {
        setData(
            'branches',
            data.branches.includes(id)
                ? data.branches.filter((branchId) => branchId !== id)
                : [...data.branches, id],
        );
    };

    return (
        <ModalForm
            title={isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna'}
            description="Admin cabang & staff dibatasi pada cabang yang ditugaskan."
        >
            {(close) => {
                const submit = (event: FormEvent) => {
                    event.preventDefault();

                    if (isEdit && user) {
                        put(users.update(user.id).url, { onSuccess: close });
                    } else {
                        post(users.store().url, { onSuccess: close });
                    }
                };

                return (
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
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

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                Password
                                {isEdit && (
                                    <span className="ml-1 text-xs font-normal text-muted-foreground">
                                        (kosongkan bila tidak diubah)
                                    </span>
                                )}
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                placeholder="Minimal 8 karakter"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="role">Peran</Label>
                            {isOwner ? (
                                <div className="flex items-center gap-2">
                                    <Badge>{user?.role_label ?? 'Owner'}</Badge>
                                    <span className="text-xs text-muted-foreground">
                                        Peran owner tidak dapat diubah.
                                    </span>
                                </div>
                            ) : (
                                <>
                                    <Select
                                        value={data.role}
                                        onValueChange={(value) =>
                                            setData('role', value)
                                        }
                                    >
                                        <SelectTrigger id="role">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roleOptions.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.role} />
                                </>
                            )}
                        </div>

                        {!isOwner && (
                            <div className="grid gap-2">
                                <Label>Cabang</Label>
                                {branchOptions.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Belum ada cabang.
                                    </p>
                                ) : (
                                    <div className="space-y-2 rounded-lg border p-3">
                                        {branchOptions.map((option) => (
                                            <label
                                                key={option.value}
                                                className="flex items-center gap-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={data.branches.includes(
                                                        option.value,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleBranch(
                                                            option.value,
                                                        )
                                                    }
                                                />
                                                {option.label}
                                            </label>
                                        ))}
                                    </div>
                                )}
                                <InputError message={errors.branches} />
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
