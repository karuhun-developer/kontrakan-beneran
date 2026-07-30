import { router } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { RouteDefinition } from '@/wayfinder';

type RowActionsProps = {
    editHref: RouteDefinition<'get'> | string;
    destroyRoute: RouteDefinition<'delete'>;
    label: string;
    canManage?: boolean;
    canDelete?: boolean;
};

function hrefToUrl(href: RouteDefinition<'get'> | string): string {
    return typeof href === 'string' ? href : href.url;
}

export function RowActions({
    editHref,
    destroyRoute,
    label,
    canManage = true,
    canDelete = true,
}: RowActionsProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    if (!canManage) {
        return null;
    }

    const handleDelete = () => {
        setProcessing(true);
        router.delete(destroyRoute.url, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    };

    return (
        <div className="flex items-center justify-end gap-1">
            <ModalLink
                href={hrefToUrl(editHref)}
                className={buttonVariants({ variant: 'ghost', size: 'icon' })}
            >
                <Pencil className="size-4" />
                <span className="sr-only">Ubah</span>
            </ModalLink>
            {canDelete && (
                <Dialog open={open} onOpenChange={setOpen}>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="text-destructive hover:text-destructive"
                        onClick={() => setOpen(true)}
                    >
                        <Trash2 className="size-4" />
                        <span className="sr-only">Hapus</span>
                    </Button>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Hapus {label}?</DialogTitle>
                            <DialogDescription>
                                Tindakan ini tidak dapat dibatalkan. Data{' '}
                                <span className="font-medium">{label}</span>{' '}
                                akan dihapus permanen.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">Batal</Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                onClick={handleDelete}
                                disabled={processing}
                            >
                                Hapus
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}
        </div>
    );
}
