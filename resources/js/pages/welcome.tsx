import { Head, Link, usePage } from '@inertiajs/react';
import {
    BellRing,
    Building2,
    CalendarCheck,
    CreditCard,
    Github,
    Heart,
    LayoutDashboard,
    LifeBuoy,
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

// Tautan repository, halaman donasi Saweria, dan kontak (Telegram).
const GITHUB_URL = 'https://github.com/karuhun-developer/kontrakan-beneran';
const SAWERIA_URL = 'https://saweria.co/warukunai';
const CONTACT_URL = 'https://t.me/bayurifkialgh';

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

// Galeri demo — ganti `image` dengan screenshot asli dan sunting judul/deskripsinya.
const GALLERY = [
    {
        title: 'Dashboard Operasional',
        description:
            'Ringkasan hunian, tagihan, dan arus kas cabang dalam satu layar.',
        image: '/demo/dashboard.png',
    },
    {
        title: 'Front Desk & Booking',
        description:
            'Timeline kamar dengan status check-in/out dan pengaman anti double-booking.',
        image: '/demo/booking.png',
    },
    {
        title: 'Tagihan & Pembayaran',
        description:
            'Invoice bulanan otomatis dengan pembayaran QRIS, Virtual Account, atau transfer manual.',
        image: '/demo/tagihan.png',
    },
    {
        title: 'Portal Penyewa',
        description:
            'Penyewa melihat tagihan, membayar online, dan memantau deposit secara mandiri.',
        image: '/demo/portal-penyewa.png',
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
            <Link href={login()}>Coba Demo</Link>
        </Button>
    );

    return (
        <>
            <Head
                title={`${name} — Manajemen Kost Multi-Cabang, Open Source`}
            />

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
                            <Button
                                asChild
                                variant="ghost"
                                className="hidden sm:inline-flex"
                            >
                                <a
                                    href={GITHUB_URL}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <Github className="size-4" />
                                    GitHub
                                </a>
                            </Button>
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <Button asChild>
                                    <Link href={login()}>Masuk</Link>
                                </Button>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero */}
                <section className="mx-auto w-full max-w-6xl px-6 py-20 md:py-28">
                    <div className="mx-auto max-w-3xl text-center">
                        <span className="inline-flex items-center gap-2 rounded-full border border-border bg-muted/50 px-4 py-1.5 text-xs font-medium text-muted-foreground">
                            <ShieldCheck className="size-3.5" />
                            Gratis & open source — self-host sepuasnya
                        </span>
                        <h1 className="mt-6 text-4xl font-semibold tracking-tight text-balance md:text-5xl">
                            Kelola kost multi-cabang dalam satu platform
                        </h1>
                        <p className="mt-5 text-lg text-pretty text-muted-foreground">
                            {name} menyatukan booking, tagihan, pembayaran,
                            deposit, hingga laporan keuangan — plus portal
                            mandiri untuk penyewa. Kode sumbernya terbuka, bebas
                            kamu pakai dan kembangkan sendiri.
                        </p>
                        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            {primaryCta}
                            <Button asChild size="lg" variant="outline">
                                <a
                                    href={GITHUB_URL}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <Github className="size-4" />
                                    Source di GitHub
                                </a>
                            </Button>
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

                {/* Galeri demo */}
                <section className="mx-auto w-full max-w-6xl px-6 py-20">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-semibold tracking-tight">
                            Lihat langsung tampilannya
                        </h2>
                        <p className="mt-3 text-muted-foreground">
                            Cuplikan antarmuka {name}. Mau coba sendiri? Masuk
                            pakai akun demo di halaman login.
                        </p>
                    </div>

                    <div className="mt-12 grid gap-6 sm:grid-cols-2">
                        {GALLERY.map((item) => (
                            <Card
                                key={item.title}
                                className="overflow-hidden border-border/60 pt-0"
                            >
                                <div className="aspect-video w-full overflow-hidden bg-muted">
                                    <img
                                        src={item.image}
                                        alt={item.title}
                                        loading="lazy"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                                <CardHeader>
                                    <CardTitle className="text-lg">
                                        {item.title}
                                    </CardTitle>
                                    <CardDescription>
                                        {item.description}
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                        ))}
                    </div>
                </section>

                {/* Open source & dukungan */}
                <section className="border-t border-border/60 bg-muted/20">
                    <div className="mx-auto w-full max-w-6xl px-6 py-20">
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="text-3xl font-semibold tracking-tight">
                                Gratis, terbuka, dan bisa didukung
                            </h2>
                            <p className="mt-3 text-muted-foreground">
                                {name} berlisensi MIT — bebas kamu self-host.
                                Kalau terbantu atau butuh dipasangkan, ini
                                caranya.
                            </p>
                        </div>

                        <div className="mx-auto mt-12 grid max-w-4xl gap-5 md:grid-cols-3">
                            <Card className="border-border/60">
                                <CardHeader>
                                    <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <Github className="size-5" />
                                    </span>
                                    <CardTitle className="mt-3 text-lg">
                                        Source di GitHub
                                    </CardTitle>
                                    <CardDescription>
                                        Clone, pelajari, dan kembangkan sendiri.
                                        Kontribusi sangat diterima.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="w-full"
                                    >
                                        <a
                                            href={GITHUB_URL}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Buka Repository
                                        </a>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card className="border-border/60">
                                <CardHeader>
                                    <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <Heart className="size-5" />
                                    </span>
                                    <CardTitle className="mt-3 text-lg">
                                        Dukung via Saweria
                                    </CardTitle>
                                    <CardDescription>
                                        Traktir kopi untuk bantu pengembangan{' '}
                                        dan perawatan proyek ini.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Button asChild className="w-full">
                                        <a
                                            href={SAWERIA_URL}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Traktir Sekarang
                                        </a>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card className="border-border/60">
                                <CardHeader>
                                    <span className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <LifeBuoy className="size-5" />
                                    </span>
                                    <CardTitle className="mt-3 text-lg">
                                        Dibantu Pasang
                                    </CardTitle>
                                    <CardDescription>
                                        Males ribet install atau butuh hosting?
                                        Kami bisa setup-kan untukmu.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="w-full"
                                    >
                                        <a
                                            href={CONTACT_URL}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Hubungi Kami
                                        </a>
                                    </Button>
                                </CardContent>
                            </Card>
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
                            <div className="shrink-0">
                                {auth.user ? (
                                    <Button asChild size="lg">
                                        <Link href={dashboard()}>
                                            Buka Dashboard
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button asChild size="lg">
                                        <Link href={register()}>
                                            Daftar Gratis
                                        </Link>
                                    </Button>
                                )}
                            </div>
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
                        <div className="flex items-center gap-4">
                            <a
                                href={GITHUB_URL}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-1.5 hover:text-foreground"
                            >
                                <Github className="size-4" />
                                GitHub
                            </a>
                            <a
                                href={SAWERIA_URL}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-1.5 hover:text-foreground"
                            >
                                <Heart className="size-4" />
                                Saweria
                            </a>
                        </div>
                        <p>© {name} — Open source (MIT).</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
