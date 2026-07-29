import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types';

type PaginationProps = {
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

export function Pagination({ links, from, to, total }: PaginationProps) {
    if (total === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-muted-foreground">
                Menampilkan {from ?? 0}–{to ?? 0} dari {total}
            </p>
            {links.length > 3 && (
                <div className="flex flex-wrap items-center gap-1">
                    {links.map((link, index) =>
                        link.url ? (
                            <Link
                                key={index}
                                href={link.url}
                                preserveScroll
                                className={cn(
                                    'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm transition-colors',
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-foreground hover:bg-accent',
                                )}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span
                                key={index}
                                className="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm text-muted-foreground/50"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ),
                    )}
                </div>
            )}
        </div>
    );
}
