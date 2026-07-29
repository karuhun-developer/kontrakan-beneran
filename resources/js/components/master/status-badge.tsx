import { Badge } from '@/components/ui/badge';

const colorClasses: Record<string, string> = {
    green: 'border-transparent bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    yellow: 'border-transparent bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
    red: 'border-transparent bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    gray: 'border-transparent bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
};

export function StatusBadge({
    label,
    active,
}: {
    label: string;
    active: boolean;
}) {
    return (
        <Badge variant={active ? 'default' : 'secondary'}>{label}</Badge>
    );
}

export function ColorBadge({ label, color }: { label: string; color: string }) {
    return (
        <Badge className={colorClasses[color] ?? colorClasses.gray}>
            {label}
        </Badge>
    );
}
