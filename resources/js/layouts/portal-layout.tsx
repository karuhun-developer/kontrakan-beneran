import { ModalRoot } from '@inertiaui/modal-react';
import { Link, usePage } from '@inertiajs/react';
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
import type { BreadcrumbItem } from '@/types';

export default function PortalLayout({
    children,
    breadcrumbs = [],
}: {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}) {
    const { auth } = usePage().props;
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

            {breadcrumbs.length > 1 && (
                <div className="border-b border-sidebar-border/70">
                    <div className="mx-auto flex h-12 w-full items-center px-4 text-neutral-500 md:max-w-5xl">
                        <Breadcrumbs breadcrumbs={breadcrumbs} />
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
