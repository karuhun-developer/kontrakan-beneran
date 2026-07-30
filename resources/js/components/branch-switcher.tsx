import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Layers } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';

export function BranchSwitcher() {
    const { branchContext } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    const { current, available, canViewAll } = branchContext;

    if (available.length === 0) {
        return null;
    }

    // Super admins in "Semua Cabang" mode have no single active branch.
    const viewingAll = canViewAll && current === null;

    const switchBranch = (branchId: number) => {
        if (branchId === current?.id) {
            return;
        }

        router.put(
            '/branch/switch',
            { branch_id: branchId },
            { preserveScroll: true, preserveState: false },
        );
    };

    const switchToAll = () => {
        if (viewingAll) {
            return;
        }

        router.put(
            '/branch/switch',
            { branch_id: 0 },
            { preserveScroll: true, preserveState: false },
        );
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group data-[state=open]:bg-sidebar-accent"
                            data-test="branch-switcher-button"
                        >
                            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                <Building2 className="size-4" />
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate text-xs text-muted-foreground">
                                    Cabang
                                </span>
                                <span className="truncate font-medium">
                                    {current?.name ??
                                        (viewingAll
                                            ? 'Semua Cabang'
                                            : 'Pilih cabang')}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'right'
                                  : 'bottom'
                        }
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Cabang
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        {canViewAll && (
                            <DropdownMenuItem
                                onSelect={switchToAll}
                                className="gap-2"
                            >
                                <Layers className="size-4 text-muted-foreground" />
                                <span className="flex-1 truncate">
                                    Semua Cabang
                                </span>
                                {viewingAll && <Check className="size-4" />}
                            </DropdownMenuItem>
                        )}
                        {available.map((branch) => (
                            <DropdownMenuItem
                                key={branch.id}
                                onSelect={() => switchBranch(branch.id)}
                                className="gap-2"
                            >
                                <Building2 className="size-4 text-muted-foreground" />
                                <span className="flex-1 truncate">
                                    {branch.name}
                                </span>
                                {branch.id === current?.id && (
                                    <Check className="size-4" />
                                )}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
