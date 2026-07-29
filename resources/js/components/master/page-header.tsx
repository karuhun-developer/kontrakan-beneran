import { ModalLink } from '@inertiaui/modal-react';
import { Plus } from 'lucide-react';
import { buttonVariants } from '@/components/ui/button';
import type { RouteDefinition } from '@/wayfinder';

type PageHeaderProps = {
    title: string;
    description?: string;
    action?: {
        label: string;
        href: RouteDefinition<'get'> | string;
    };
};

function hrefToUrl(href: RouteDefinition<'get'> | string): string {
    return typeof href === 'string' ? href : href.url;
}

export function PageHeader({ title, description, action }: PageHeaderProps) {
    return (
        <div className="flex flex-wrap items-end justify-between gap-4">
            <div className="space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {action && (
                <ModalLink
                    href={hrefToUrl(action.href)}
                    className={buttonVariants()}
                >
                    <Plus className="size-4" />
                    {action.label}
                </ModalLink>
            )}
        </div>
    );
}
