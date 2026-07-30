import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatRupiah, formatRupiahShort } from '@/lib/format';
import { cn } from '@/lib/utils';
import cashflows from '@/routes/cashflows';
import type { CashflowCalendar as CalendarData } from '@/types/master';

function SummaryTile({
    label,
    value,
    tone,
}: {
    label: string;
    value: string;
    tone: string;
}) {
    return (
        <div className="flex-1 rounded-lg border px-4 py-3">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className={cn('text-lg font-semibold', tone)}>{value}</p>
        </div>
    );
}

export function CashflowCalendar({ calendar }: { calendar: CalendarData }) {
    const goToMonth = (month: string) => {
        router.get(
            cashflows.index().url,
            { month },
            { only: ['calendar'], preserveState: true, preserveScroll: true },
        );
    };

    const income = calendar.categories.filter((c) => c.type === 'income');
    const expense = calendar.categories.filter((c) => c.type === 'expense');

    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => goToMonth(calendar.prev_month)}
                        aria-label="Bulan sebelumnya"
                    >
                        <ChevronLeft className="size-4" />
                    </Button>
                    <div className="min-w-40 text-center text-sm font-semibold capitalize">
                        {calendar.month_label}
                    </div>
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => goToMonth(calendar.next_month)}
                        aria-label="Bulan berikutnya"
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </div>

            <div className="flex flex-wrap gap-3">
                <SummaryTile
                    label="Pemasukan"
                    value={formatRupiah(calendar.summary.income)}
                    tone="text-green-600 dark:text-green-400"
                />
                <SummaryTile
                    label="Pengeluaran"
                    value={formatRupiah(calendar.summary.expense)}
                    tone="text-red-600 dark:text-red-400"
                />
                <SummaryTile
                    label="Selisih"
                    value={formatRupiah(calendar.summary.net)}
                    tone={
                        calendar.summary.net < 0
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-foreground'
                    }
                />
            </div>

            <div className="grid gap-4 lg:grid-cols-[1fr_18rem]">
                {/* Month grid */}
                <div className="overflow-hidden rounded-lg border">
                    <div className="grid grid-cols-7 border-b bg-muted/50 text-center text-xs font-medium text-muted-foreground">
                        {calendar.weekdays.map((weekday) => (
                            <div key={weekday} className="py-2">
                                {weekday}
                            </div>
                        ))}
                    </div>
                    <div className="grid grid-cols-7">
                        {calendar.weeks.flat().map((cell) => (
                            <div
                                key={cell.date}
                                className={cn(
                                    'flex min-h-20 flex-col gap-1 border-r border-b p-1.5 last:border-r-0',
                                    !cell.in_month && 'bg-muted/30',
                                    cell.is_weekend &&
                                        cell.in_month &&
                                        'bg-muted/20',
                                )}
                            >
                                <span
                                    className={cn(
                                        'text-right text-xs',
                                        cell.in_month
                                            ? 'text-muted-foreground'
                                            : 'text-muted-foreground/40',
                                        cell.is_today &&
                                            'ml-auto flex size-5 items-center justify-center rounded-full bg-primary font-semibold text-primary-foreground',
                                    )}
                                >
                                    {cell.day}
                                </span>
                                <div className="flex flex-col gap-0.5">
                                    {cell.income > 0 && (
                                        <span className="truncate rounded bg-green-100 px-1 py-0.5 text-[10px] font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                            +{formatRupiahShort(cell.income)}
                                        </span>
                                    )}
                                    {cell.expense > 0 && (
                                        <span className="truncate rounded bg-red-100 px-1 py-0.5 text-[10px] font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                            −{formatRupiahShort(cell.expense)}
                                        </span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Per-category breakdown */}
                <Card>
                    <CardContent className="flex flex-col gap-5 py-5">
                        <CategoryList
                            title="Pemasukan per Kategori"
                            rows={income}
                            tone="text-green-600 dark:text-green-400"
                            sign="+"
                        />
                        <CategoryList
                            title="Pengeluaran per Kategori"
                            rows={expense}
                            tone="text-red-600 dark:text-red-400"
                            sign="−"
                        />
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

function CategoryList({
    title,
    rows,
    tone,
    sign,
}: {
    title: string;
    rows: CalendarData['categories'];
    tone: string;
    sign: string;
}) {
    return (
        <div className="flex flex-col gap-2">
            <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                {title}
            </p>
            {rows.length === 0 ? (
                <p className="text-sm text-muted-foreground">Belum ada.</p>
            ) : (
                <ul className="flex flex-col gap-1.5">
                    {rows.map((row) => (
                        <li
                            key={`${row.type}-${row.name}`}
                            className="flex items-center justify-between gap-2 text-sm"
                        >
                            <span className="truncate">{row.name}</span>
                            <span className={cn('font-medium', tone)}>
                                {sign}
                                {formatRupiah(row.total)}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
