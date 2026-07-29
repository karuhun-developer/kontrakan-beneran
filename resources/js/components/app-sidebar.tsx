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

const operationalNavItems: NavItem[] = [
    {
        title: 'Booking',
        href: bookings.index(),
        icon: CalendarCheck,
    },
    {
        title: 'Tagihan',
        href: invoices.index(),
        icon: ReceiptText,
    },
    {
        title: 'Kas',
        href: cashflows.index(),
        icon: Wallet,
    },
    {
        title: 'Deposit',
        href: deposits.index(),
        icon: PiggyBank,
    },
    {
        title: 'Laundry',
        href: laundry.index(),
        icon: Shirt,
    },
];

const masterNavItems: NavItem[] = [
    {
        title: 'Kamar',
        href: rooms.index(),
        icon: BedDouble,
    },
    {
        title: 'Tipe Kamar',
        href: roomTypes.index(),
        icon: Tags,
    },
    {
        title: 'Fasilitas',
        href: facilities.index(),
        icon: Sofa,
    },
    {
        title: 'Penyewa',
        href: penyewa.index(),
        icon: Users,
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
    const canViewReports = useCan('report.view');

    const analyticsNavItems: NavItem[] = canViewReports
        ? [
              {
                  title: 'Laporan',
                  href: reports.index(),
                  icon: ChartColumn,
              },
          ]
        : [];

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
                <NavMain items={operationalNavItems} label="Operasional" />
                {analyticsNavItems.length > 0 && (
                    <NavMain items={analyticsNavItems} label="Analitik" />
                )}
                <NavMain items={masterNavItems} label="Master Data" />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
