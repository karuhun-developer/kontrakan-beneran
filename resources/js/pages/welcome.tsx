import { Head, Link, usePage } from '@inertiajs/react';
import {
    BellRing,
    Building2,
    CalendarCheck,
    CreditCard,
    LayoutDashboard,
    PiggyBank,
    ReceiptText,
    ShieldCheck,
    Users,
    Wallet,
    WashingMachine,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';

const FEATURES = [
    {
        icon: Building2,
        title: 'Multi-Bisnis & Cabang',
        description:
            'Kelola banyak kost dalam satu akun. Data setiap cabang terisolasi rapi dengan hak akses per peran.',
    },
    {
        icon: CalendarCheck,
        title: 'Booking & Front Desk',
        description:
            'Check-in, check-out, dan perpanjangan sewa dengan pengaman anti double-booking bawaan.',
    },
    {
        icon: ReceiptText,
        title: 'Tagihan Otomatis',
        description:
            'Invoice berulang tiap bulan lengkap dengan denda keterlambatan yang dihitung otomatis.',
    },
    {
        icon: CreditCard,
        title: 'Pembayaran Fleksibel',
        description:
            'Terima QRIS, Virtual Account, dan transfer manual dengan bukti bayar — gateway bisa dipilih.',
    },
    {
        icon: PiggyBank,
        title: 'Deposit & Ledger',
        description:
            'Catat deposit penyewa beserta buku transaksinya agar saldo selalu transparan.',
    },
    {
        icon: WashingMachine,
        title: 'Layanan Laundry',
        description:
            'Kelola order laundry dan bebankan ke deposit atau terbitkan sebagai tagihan.',
    },
    {
        icon: Wallet,
        title: 'Buku Kas',
        description:
            'Pantau arus kas masuk dan keluar per kategori dalam tampilan kalender bulanan.',
    },
    {
        icon: BellRing,
        title: 'Notifikasi Jatuh Tempo',
        description:
            'Pengingat tagihan otomatis untuk penyewa maupun staf sebelum jatuh tempo.',
    },
    {
        icon: LayoutDashboard,
        title: 'Dashboard & Laporan',
        description:
            'Ringkasan operasional dan keuangan per cabang, siap diekspor ke CSV.',
    },
];

export default function Welcome() {
    const { auth, name } = usePage().props;

    const primaryCta = auth.user ? (
        <Button asChild size="lg">
            <Link href={dashboard()}>Buka Dashboard</Link>
        </Button>
    ) : (
        <Button asChild size="lg">
            <Link href={register()}>Mulai Gratis</Link>
        </Button>
    );

    return (
        <>
            <Head title={`${name} — Manajemen Kost Multi-Cabang`} />

            <div className="flex min-h-svh flex-col bg-background text-foreground">
                {/* Header */}
                <header className="sticky top-0 z-10 border-b border-border/60 bg-background/80 backdrop-blur">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-4">
                        <Link
                            href="/"
                            className="flex items-center gap-2 font-semibold"
                        >
                            <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-4 fill-current" />
                            </span>
                            {name}
                        </Link>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild variant="ghost">
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        className="hidden sm:inline-flex"
                                    >
                                        <Link href={login()}>Masuk</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>Daftar</Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero */}
                <section className="mx-auto w-full max-w-6xl px-6 py-20 md:py-28">
                    <div className="mx-auto max-w-3xl text-center">
                        <span className="inline-flex items-center gap-2 rounded-full border border-border bg-muted/50 px-4 py-1.5 text-xs font-medium text-muted-foreground">
                            <ShieldCheck className="size-3.5" />
                            Manajemen kost yang tenang & terpercaya
                        </span>
                        <h1 className="mt-6 text-4xl font-semibold tracking-tight text-balance md:text-5xl">
                            Kelola kost multi-cabang dalam satu platform
                        </h1>
                        <p className="mt-5 text-lg text-muted-foreground text-pretty">
                            {name} menyatukan booking, tagihan, pembayaran,
                            deposit, hingga laporan keuangan — plus portal
                            mandiri untuk penyewa. Rapi, aman, dan siap tumbuh
                            bersama bisnis kamu.
                        </p>
                        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            {primaryCta}
                            {!auth.user && (
                                <Button asChild size="lg" variant="outline">
                                    <Link href={login()}>Masuk ke Akun</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section className="border-t border-border/60 bg-muted/20">
                    <div className="mx-auto w-full max-w-6xl px-6 py-20">
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="text-3xl font-semibold tracking-tight">
                                Semua kebutuhan operasional kost
                            </h2>
                            <p className="mt-3 text-muted-foreground">
                                Dari penyewa masuk sampai laporan bulanan —
                                semua alurnya sudah tersedia dan saling
                                terhubung.
                            </p>
                        </div>

                        <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {FEATURES.map((feature) => (
                                <Card
                                    key={feature.title}
                                    className="border-border/60"
                                >
                                    <CardHeader>
                                        <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <feature.icon className="size-5" />
                                        </span>
                                        <CardTitle className="mt-3 text-lg">
                                            {feature.title}
                                        </CardTitle>
                                        <CardDescription>
                                            {feature.description}
                                        </CardDescription>
                                    </CardHeader>
                                </Card>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Portal penyewa */}
                <section className="mx-auto w-full max-w-6xl px-6 py-20">
                    <Card className="overflow-hidden border-border/60 bg-card">
                        <CardContent className="flex flex-col items-start gap-6 p-8 md:flex-row md:items-center md:justify-between md:p-12">
                            <div className="max-w-xl space-y-3">
                                <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <Users className="size-5" />
                                </span>
                                <h2 className="text-2xl font-semibold tracking-tight">
                                    Portal mandiri untuk penyewa
                                </h2>
                                <p className="text-muted-foreground">
                                    Penyewa bisa melihat tagihan, membayar
                                    online, mengecek deposit, memantau laundry,
                                    dan menerima notifikasi — tanpa perlu
                                    merepotkan admin.
                                </p>
                            </div>
                            <div className="shrink-0">{primaryCta}</div>
                        </CardContent>
                    </Card>
                </section>

                {/* Footer */}
                <footer className="mt-auto border-t border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-3 px-6 py-8 text-sm text-muted-foreground sm:flex-row">
                        <div className="flex items-center gap-2">
                            <AppLogoIcon className="size-4 fill-current" />
                            <span>{name}</span>
                        </div>
                        <p>
                            © {name} — Aplikasi manajemen kost multi-bisnis &
                            multi-cabang.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
