import { router } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Fragment } from 'react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { formatDate } from '@/lib/format';
import bookings from '@/routes/bookings';
import type { BookingCalendar, TimelineRoom } from '@/types/master';

const DAY_WIDTH = 40;
const LABEL_WIDTH = 160;

const barColorClasses: Record<string, string> = {
    green: 'bg-green-500 text-white hover:bg-green-500/90',
    blue: 'bg-blue-500 text-white hover:bg-blue-500/90',
    yellow: 'bg-yellow-400 text-yellow-950 hover:bg-yellow-400/90',
    red: 'bg-red-500 text-white hover:bg-red-500/90',
    gray: 'bg-neutral-400 text-white hover:bg-neutral-400/90 dark:bg-neutral-600',
};

const legend = [
    { color: 'blue', label: 'Confirmed' },
    { color: 'green', label: 'Check-in' },
    { color: 'yellow', label: 'Pending' },
    { color: 'gray', label: 'Check-out' },
];

export function BookingTimeline({ calendar }: { calendar: BookingCalendar }) {
    const goToMonth = (month: string) => {
        router.get(
            bookings.index().url,
            { month },
            { only: ['calendar'], preserveState: true, preserveScroll: true },
        );
    };

    const gridTemplateColumns = `${LABEL_WIDTH}px repeat(${calendar.days_in_month}, minmax(${DAY_WIDTH}px, 1fr))`;
    const minWidth = LABEL_WIDTH + calendar.days_in_month * DAY_WIDTH;

    // Flatten groups into positioned rows: a header row per room type,
    // followed by its rooms. Row 1 is the day-header, so bodies start at 2.
    type Row =
        | { kind: 'group'; key: string; name: string; gridRow: number }
        | { kind: 'room'; key: string; room: TimelineRoom; gridRow: number };

    const rows: Row[] = [];
    let cursor = 2;
    for (const group of calendar.groups) {
        rows.push({
            kind: 'group',
            key: `g-${group.name}`,
            name: group.name,
            gridRow: cursor++,
        });
        for (const room of group.rooms) {
            rows.push({
                kind: 'room',
                key: `r-${room.id}`,
                room,
                gridRow: cursor++,
            });
        }
    }

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
                <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                    {legend.map((item) => (
                        <span
                            key={item.color}
                            className="flex items-center gap-1.5"
                        >
                            <span
                                className={cn(
                                    'size-3 rounded-sm',
                                    barColorClasses[item.color],
                                )}
                            />
                            {item.label}
                        </span>
                    ))}
                </div>
            </div>

            {calendar.groups.length === 0 ? (
                <div className="rounded-lg border border-dashed py-12 text-center text-sm text-muted-foreground">
                    Belum ada kamar di cabang ini.
                </div>
            ) : (
                <div className="overflow-x-auto rounded-lg border">
                    <TooltipProvider delayDuration={100}>
                        <div
                            className="grid text-sm"
                            style={{ gridTemplateColumns, minWidth }}
                        >
                            {/* Header */}
                            <div className="sticky left-0 z-20 flex items-center border-r border-b bg-muted/50 px-3 py-2 text-xs font-medium text-muted-foreground">
                                Kamar
                            </div>
                            {calendar.days.map((day, index) => (
                                <div
                                    key={index}
                                    className={cn(
                                        'flex flex-col items-center justify-center border-b py-1.5 text-center',
                                        day.is_weekend && 'bg-muted/50',
                                        day.is_today &&
                                            'bg-primary/10 font-semibold text-primary',
                                    )}
                                >
                                    <span className="text-sm leading-none">
                                        {day.day}
                                    </span>
                                    <span className="text-[10px] leading-none text-muted-foreground uppercase">
                                        {day.weekday}
                                    </span>
                                </div>
                            ))}

                            {/* Rows */}
                            {rows.map((row) => {
                                const gridRow = row.gridRow;

                                if (row.kind === 'group') {
                                    return (
                                        <div
                                            key={row.key}
                                            className="sticky left-0 z-30 flex items-center border-t bg-muted/70 px-3 py-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                            style={{
                                                gridColumn: `1 / span ${calendar.days_in_month + 1}`,
                                                gridRow,
                                            }}
                                        >
                                            {row.name}
                                        </div>
                                    );
                                }

                                const room = row.room;

                                return (
                                    <Fragment key={row.key}>
                                        <div
                                            className="sticky left-0 z-20 flex flex-col justify-center border-t border-r bg-card px-3 py-2 pl-5"
                                            style={{ gridColumn: 1, gridRow }}
                                        >
                                            <span className="leading-tight font-medium">
                                                {room.number}
                                            </span>
                                            {room.name && (
                                                <span className="truncate text-xs text-muted-foreground">
                                                    {room.name}
                                                </span>
                                            )}
                                        </div>

                                        {calendar.days.map((day, dayIndex) => (
                                            <div
                                                key={dayIndex}
                                                className={cn(
                                                    'h-12 border-t border-l border-border/50',
                                                    day.is_weekend &&
                                                        'bg-muted/30',
                                                    day.is_today &&
                                                        'bg-primary/5',
                                                )}
                                                style={{
                                                    gridColumn: dayIndex + 2,
                                                    gridRow,
                                                }}
                                            />
                                        ))}

                                        {room.bars.map((bar) => (
                                            <div
                                                key={bar.id}
                                                className="z-10 flex items-center px-1 py-1.5"
                                                style={{
                                                    gridColumn: `${bar.start_index + 2} / span ${bar.span}`,
                                                    gridRow,
                                                }}
                                            >
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <ModalLink
                                                            href={
                                                                bookings.show(
                                                                    bar.id,
                                                                ).url
                                                            }
                                                            className={cn(
                                                                'flex h-full w-full items-center gap-1 truncate rounded-md px-2 text-xs font-medium shadow-sm transition',
                                                                barColorClasses[
                                                                    bar
                                                                        .status_color
                                                                ] ??
                                                                    barColorClasses.gray,
                                                                bar.continues_before &&
                                                                    'rounded-l-none',
                                                                bar.continues_after &&
                                                                    'rounded-r-none',
                                                            )}
                                                        >
                                                            <span className="truncate">
                                                                {bar.penyewa}
                                                            </span>
                                                        </ModalLink>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p className="font-medium">
                                                            {room.number}
                                                            {room.name
                                                                ? ` · ${room.name}`
                                                                : ''}
                                                        </p>
                                                        <p>{bar.penyewa}</p>
                                                        <p className="text-muted-foreground">
                                                            {formatDate(
                                                                bar.start_date,
                                                            )}{' '}
                                                            →{' '}
                                                            {formatDate(
                                                                bar.end_date,
                                                            )}
                                                        </p>
                                                        <p>
                                                            {bar.status_label}
                                                        </p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </div>
                                        ))}
                                    </Fragment>
                                );
                            })}
                        </div>
                    </TooltipProvider>
                </div>
            )}
        </div>
    );
}
