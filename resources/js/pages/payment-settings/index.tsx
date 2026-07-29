import { Form, Head } from '@inertiajs/react';
import { Landmark } from 'lucide-react';
import PaymentSettingController from '@/actions/App/Http/Controllers/Settings/PaymentSettingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/payment-settings';
import type { BreadcrumbItem } from '@/types';

type PaymentBranch = {
    id: number;
    name: string;
    bank_name: string | null;
    bank_account_number: string | null;
    bank_account_holder: string | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rekening Pembayaran', href: edit() },
];

export default function PaymentSettings({ branch }: { branch: PaymentBranch }) {
    return (
        <div className="px-4 py-6">
            <Head title="Rekening Pembayaran" />

            <Heading
                title="Rekening Pembayaran"
                description="Rekening tujuan transfer manual yang ditampilkan ke penyewa saat membayar tagihan."
            />

            <div className="max-w-xl">
                <Form
                    {...PaymentSettingController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex items-center gap-2 rounded-lg border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                                <Landmark className="size-4 shrink-0" />
                                Kosongkan semua kolom untuk menyembunyikan opsi
                                transfer manual dari penyewa.
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="bank_name">Nama Bank</Label>
                                <Input
                                    id="bank_name"
                                    name="bank_name"
                                    defaultValue={branch.bank_name ?? ''}
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
                                    name="bank_account_number"
                                    defaultValue={
                                        branch.bank_account_number ?? ''
                                    }
                                    placeholder="mis. 1234567890"
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
                                    name="bank_account_holder"
                                    defaultValue={
                                        branch.bank_account_holder ?? ''
                                    }
                                    placeholder={branch.name}
                                />
                                <InputError
                                    message={errors.bank_account_holder}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing}>Simpan</Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </div>
    );
}

PaymentSettings.layout = { breadcrumbs };
