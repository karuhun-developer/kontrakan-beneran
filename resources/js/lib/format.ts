const rupiahFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

export function formatRupiah(value: number | null | undefined): string {
    return rupiahFormatter.format(value ?? 0);
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
