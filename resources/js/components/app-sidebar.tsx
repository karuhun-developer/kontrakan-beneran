import { Link } from '@inertiajs/react';
import {
    BedDouble,
    BookOpen,
    CalendarCheck,
    FolderGit2,
    LayoutGrid,
    Sofa,
    Tags,
    Users,
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
import bookings from '@/routes/bookings';
import facilities from '@/routes/facilities';
import penyewa from '@/routes/penyewa';
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
                <NavMain items={masterNavItems} label="Master Data" />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
