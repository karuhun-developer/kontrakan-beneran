import { ModalRoot } from '@inertiaui/modal-react';
import { Link, usePage } from '@inertiajs/react';
import {
    History,
    KeyRound,
    LayoutDashboard,
    ReceiptText,
    Shirt,
    Wallet,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useFlashToast } from '@/hooks/use-flash-toast';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { dashboard as portalDashboard } from '@/routes/portal';
import portalDeposits from '@/routes/portal/deposits';
import portalInvoices from '@/routes/portal/invoices';
import portalLaundry from '@/routes/portal/laundry';
import portalStays from '@/routes/portal/stays';
import { edit as securityEdit } from '@/routes/security';
import type { BreadcrumbItem } from '@/types';

const navItems = [
    { label: 'Dashboard', icon: LayoutDashboard, href: portalDashboard().url },
    { label: 'Tagihan', icon: ReceiptText, href: portalInvoices.index().url },
    { label: 'Deposit', icon: Wallet, href: portalDeposits.index().url },
    { label: 'Laundry', icon: Shirt, href: portalLaundry.index().url },
    { label: 'Riwayat', icon: History, href: portalStays.index().url },
];

export default function PortalLayout({
    children,
    breadcrumbs = [],
}: {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}) {
    const { auth, portal } = usePage().props;
    const currentUrl = usePage().url;
    const getInitials = useInitials();

    useFlashToast();

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <header className="border-b border-sidebar-border/80">
                <div className="mx-auto flex h-16 w-full items-center px-4 md:max-w-5xl">
                    <Link
                        href="/portal"
                        className="flex items-center gap-2 font-semibold"
                    >
                        <div className="flex size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                            <AppLogoIcon className="size-5 fill-current" />
                        </div>
                        <span>Portal Penyewa</span>
                    </Link>

                    <div className="ml-auto flex items-center gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="size-10 rounded-full p-1"
                                >
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage
                                            src={auth.user?.avatar}
                                            alt={auth.user?.name}
                                        />
                                        <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {getInitials(auth.user?.name ?? '')}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                {auth.user && (
                                    <UserMenuContent user={auth.user} />
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </header>

            <nav className="border-b border-sidebar-border/70">
                <div className="mx-auto flex w-full items-center gap-1 px-4 md:max-w-5xl">
                    {navItems.map((item) => {
                        const active =
                            item.href === '/portal'
                                ? currentUrl === '/portal'
                                : currentUrl.startsWith(item.href);

                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition-colors',
                                    active
                                        ? 'border-primary text-foreground'
                                        : 'border-transparent text-muted-foreground hover:text-foreground',
                                )}
                            >
                                <item.icon className="size-4" />
                                {item.label}
                            </Link>
                        );
                    })}
                </div>
            </nav>

            {breadcrumbs.length > 1 && (
                <div className="border-b border-sidebar-border/70">
                    <div className="mx-auto flex h-12 w-full items-center px-4 text-neutral-500 md:max-w-5xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
                    </div>
                </div>
            )}

            {portal?.must_change_password && (
                <div className="border-b border-amber-300/60 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-950/40">
                    <div className="mx-auto flex w-full flex-wrap items-center gap-3 px-4 py-3 md:max-w-5xl">
                        <KeyRound className="size-4 shrink-0 text-amber-600 dark:text-amber-400" />
                        <p className="text-sm text-amber-800 dark:text-amber-200">
                            Kamu masih memakai kata sandi default. Ganti sekarang
                            demi keamanan akunmu.
                        </p>
                        <Button
                            asChild
                            size="sm"
                            variant="outline"
                            className="ml-auto border-amber-300 text-amber-800 hover:bg-amber-100 dark:border-amber-500/40 dark:text-amber-100 dark:hover:bg-amber-900/40"
                        >
                            <Link href={securityEdit().url}>Ganti Kata Sandi</Link>
                        </Button>
                    </div>
                </div>
            )}

            <main className="mx-auto w-full flex-1 px-4 py-6 md:max-w-5xl">
                {children}
            </main>

            <ModalRoot />
        </div>
    );
}
