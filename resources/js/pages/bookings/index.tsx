import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { ColorBadge } from '@/components/master/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCan } from '@/hooks/use-can';
import { formatDate, formatRupiah } from '@/lib/format';
import bookings from '@/routes/bookings';
import type { BookingListItem, Paginated } from '@/types';

type PageProps = {
    bookings: Paginated<BookingListItem>;
};

export default function BookingsIndex() {
    const { bookings: data } = usePage<PageProps>().props;
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
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
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
                                            {formatDate(booking.start_date)}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(booking.end_date)}
                                        </TableCell>
                                        <TableCell>
                                            {formatRupiah(booking.monthly_price)}
                                        </TableCell>
                                        <TableCell>
                                            <ColorBadge
                                                label={booking.status_label}
                                                color={booking.status_color}
                                            />
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
            </div>
        </>
    );
}

BookingsIndex.layout = {
    breadcrumbs: [{ title: 'Booking', href: bookings.index() }],
};
