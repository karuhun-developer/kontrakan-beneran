const rupiahFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export function formatRupiah(value: number | null | undefined): string {
    return rupiahFormatter.format(value ?? 0);
}

/** Compact rupiah for tight spaces (calendar cells): 1.5jt, 750rb, 2,1M. */
export function formatRupiahShort(value: number | null | undefined): string {
    const amount = value ?? 0;
    const abs = Math.abs(amount);

    if (abs >= 1_000_000_000) {
        return `${trimZero(amount / 1_000_000_000)}M`;
    }

    if (abs >= 1_000_000) {
        return `${trimZero(amount / 1_000_000)}jt`;
    }

    if (abs >= 1_000) {
        return `${trimZero(amount / 1_000)}rb`;
    }

    return String(amount);
}

function trimZero(value: number): string {
    return value.toFixed(1).replace(/\.0$/, '').replace('.', ',');
}

const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

/** Format an ISO date string (YYYY-MM-DD) as a short Indonesian date. */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const parsed = new Date(`${value}T00:00:00`);

    return Number.isNaN(parsed.getTime()) ? '—' : dateFormatter.format(parsed);
}

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

/** Format a datetime string (YYYY-MM-DD HH:MM:SS) as an Indonesian date-time. */
export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const parsed = new Date(value.replace(' ', 'T'));

    return Number.isNaN(parsed.getTime())
        ? '—'
        : dateTimeFormatter.format(parsed);
}
