import { Head, usePage } from '@inertiajs/react';
import { Building2, DoorOpen, ReceiptText, Wallet } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Auth } from '@/types';

type PortalDashboardProps = {
    auth: Auth;
    penyewa: {
        id: number;
        name: string;
        branch: string | null;
    } | null;
};

export default function PortalDashboard() {
    const { penyewa, auth } = usePage<PortalDashboardProps>().props;
    const displayName = penyewa?.name ?? auth.user?.name ?? 'Penyewa';

    const stats = [
        {
            title: 'Kamar Saya',
            description: 'Belum ada kamar aktif',
            icon: DoorOpen,
        },
        {
            title: 'Tagihan',
            description: 'Belum ada tagihan',
            icon: ReceiptText,
        },
        {
            title: 'Deposit',
            description: 'Belum ada deposit',
            icon: Wallet,
        },
    ];

    return (
        <>
            <Head title="Portal" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Halo, {displayName}
                    </h1>
                    {penyewa?.branch && (
                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <Building2 className="size-4" />
                            {penyewa.branch}
                        </p>
                    )}
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {stats.map((stat) => (
                        <Card key={stat.title}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    {stat.title}
                                </CardTitle>
                                <stat.icon className="size-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <CardDescription>
                                    {stat.description}
                                </CardDescription>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Selamat datang di portal penyewa</CardTitle>
                        <CardDescription>
                            Di sini kamu bisa melihat kamar, tagihan, riwayat
                            pembayaran, dan deposit. Fitur-fitur ini akan
                            tersedia bertahap.
                        </CardDescription>
                    </CardHeader>
                </Card>
            </div>
        </>
    );
}
