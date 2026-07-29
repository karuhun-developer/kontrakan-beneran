import { Head, usePage } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { CalendarRange, Eye, Table2 } from 'lucide-react';
import { BookingTimeline } from '@/components/bookings/booking-timeline';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { ColorBadge } from '@/components/master/status-badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useCan } from '@/hooks/use-can';
import { formatDate, formatRupiah } from '@/lib/format';
import bookings from '@/routes/bookings';
import type {
    BookingCalendar,
    BookingListItem,
    Paginated,
} from '@/types/master';

type PageProps = {
    bookings: Paginated<BookingListItem>;
    calendar: BookingCalendar;
};

export default function BookingsIndex() {
    const { bookings: data, calendar } = usePage<PageProps>().props;
    const canManage = useCan('booking.manage');

    return (
        <>
            <Head title="Booking" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Booking"
                    description="Reservasi kamar per cabang dengan cek ketersediaan otomatis."
                    action={
                        canManage
                            ? { label: 'Buat Booking', href: bookings.create() }
                            : undefined
                    }
                />

                <Tabs defaultValue="calendar" className="flex flex-col gap-4">
                    <TabsList>
                        <TabsTrigger value="calendar">
                            <CalendarRange className="size-4" />
                            Kalender
                        </TabsTrigger>
                        <TabsTrigger value="table">
                            <Table2 className="size-4" />
                            Tabel
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="calendar">
                        <Card>
                            <CardContent>
                                <BookingTimeline calendar={calendar} />
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="table" className="flex flex-col gap-6">
                        <Card>
                            <CardContent className="px-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Kamar</TableHead>
                                            <TableHead>Penyewa</TableHead>
                                            <TableHead>Mulai</TableHead>
                                            <TableHead>Selesai</TableHead>
                                            <TableHead>Harga / Bulan</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="w-12" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {data.data.length === 0 && (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={7}
                                                    className="py-10 text-center text-muted-foreground"
                                                >
                                                    Belum ada booking.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                        {data.data.map((booking) => (
                                            <TableRow key={booking.id}>
                                                <TableCell className="font-medium">
                                                    {booking.room ?? '—'}
                                                </TableCell>
                                                <TableCell>
                                                    {booking.penyewa ?? '—'}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDate(
                                                        booking.start_date,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDate(booking.end_date)}
                                                </TableCell>
                                                <TableCell>
                                                    {formatRupiah(
                                                        booking.monthly_price,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <ColorBadge
                                                        label={
                                                            booking.status_label
                                                        }
                                                        color={
                                                            booking.status_color
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <ModalLink
                                                        href={
                                                            bookings.show(
                                                                booking.id,
                                                            ).url
                                                        }
                                                        className={buttonVariants(
                                                            {
                                                                variant: 'ghost',
                                                                size: 'icon',
                                                            },
                                                        )}
                                                    >
                                                        <Eye className="size-4" />
                                                        <span className="sr-only">
                                                            Detail
                                                        </span>
                                                    </ModalLink>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>

                        <Pagination
                            links={data.links}
                            from={data.from}
                            to={data.to}
                            total={data.total}
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}

BookingsIndex.layout = {
    breadcrumbs: [{ title: 'Booking', href: bookings.index() }],
};
