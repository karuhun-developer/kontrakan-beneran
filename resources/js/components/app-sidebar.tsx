import { Link } from '@inertiajs/react';
import {
    BedDouble,
    BookOpen,
    CalendarCheck,
    ChartColumn,
    FolderGit2,
    LayoutGrid,
    PiggyBank,
    ReceiptText,
    Shirt,
    Sofa,
    Tags,
    Users,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { BranchSwitcher } from '@/components/branch-switcher';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCan } from '@/hooks/use-can';
import bookings from '@/routes/bookings';
import cashflows from '@/routes/cashflows';
import deposits from '@/routes/deposits';
import facilities from '@/routes/facilities';
import invoices from '@/routes/invoices';
import laundry from '@/routes/laundry';
import penyewa from '@/routes/penyewa';
import reports from '@/routes/reports';
import roomTypes from '@/routes/room-types';
import rooms from '@/routes/rooms';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

type GatedNavItem = NavItem & { permission: string };

const operationalNavItems: GatedNavItem[] = [
    {
        title: 'Booking',
        href: bookings.index(),
        icon: CalendarCheck,
        permission: 'booking.view',
    },
    {
        title: 'Tagihan',
        href: invoices.index(),
        icon: ReceiptText,
        permission: 'invoice.view',
    },
    {
        title: 'Kas',
        href: cashflows.index(),
        icon: Wallet,
        permission: 'cashflow.view',
    },
    {
        title: 'Deposit',
        href: deposits.index(),
        icon: PiggyBank,
        permission: 'deposit.view',
    },
    {
        title: 'Laundry',
        href: laundry.index(),
        icon: Shirt,
        permission: 'laundry.view',
    },
];

const analyticsNavItems: GatedNavItem[] = [
    {
        title: 'Laporan',
        href: reports.index(),
        icon: ChartColumn,
        permission: 'report.view',
    },
];

const masterNavItems: GatedNavItem[] = [
    {
        title: 'Kamar',
        href: rooms.index(),
        icon: BedDouble,
        permission: 'room.view',
    },
    {
        title: 'Tipe Kamar',
        href: roomTypes.index(),
        icon: Tags,
        permission: 'roomtype.view',
    },
    {
        title: 'Fasilitas',
        href: facilities.index(),
        icon: Sofa,
        permission: 'facility.view',
    },
    {
        title: 'Penyewa',
        href: penyewa.index(),
        icon: Users,
        permission: 'penyewa.view',
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const can = useCan();

    const operational = operationalNavItems.filter((item) =>
        can(item.permission),
    );
    const analytics = analyticsNavItems.filter((item) => can(item.permission));
    const master = masterNavItems.filter((item) => can(item.permission));

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <BranchSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {operational.length > 0 && (
                    <NavMain items={operational} label="Operasional" />
                )}
                {analytics.length > 0 && (
                    <NavMain items={analytics} label="Analitik" />
                )}
                {master.length > 0 && (
                    <NavMain items={master} label="Master Data" />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
