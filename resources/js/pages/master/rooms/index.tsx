import { Head, usePage } from '@inertiajs/react';
import { ColorBadge, StatusBadge } from '@/components/master/status-badge';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { RowActions } from '@/components/master/row-actions';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCanManageMaster } from '@/hooks/use-can';
import { formatRupiah } from '@/lib/format';
import rooms from '@/routes/rooms';
import type { Paginated, RoomListItem } from '@/types';

type PageProps = {
    rooms: Paginated<RoomListItem>;
};

export default function RoomsIndex() {
    const { rooms: data } = usePage<PageProps>().props;
    const canManage = useCanManageMaster();

    return (
        <>
            <Head title="Kamar" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Kamar"
                    description="Kelola kamar per cabang beserta status huniannya."
                    action={
                        canManage
                            ? { label: 'Tambah Kamar', href: rooms.create() }
                            : undefined
                    }
                />

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nomor</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead>Lantai</TableHead>
                                    <TableHead>Harga / Bulan</TableHead>
                                    <TableHead>Hunian</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada kamar.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((room) => (
                                    <TableRow key={room.id}>
                                        <TableCell className="font-medium">
                                            {room.number}
                                            {room.name && (
                                                <span className="ml-1 text-muted-foreground">
                                                    · {room.name}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {room.room_type ?? '—'}
                                        </TableCell>
                                        <TableCell>{room.floor}</TableCell>
                                        <TableCell>
                                            {formatRupiah(room.monthly_price)}
                                        </TableCell>
                                        <TableCell>
                                            <ColorBadge
                                                label={room.room_status_label}
                                                color={room.room_status_color}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={room.status_label}
                                                active={
                                                    room.status === 'active'
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <RowActions
                                                label={`Kamar ${room.number}`}
                                                editHref={rooms.edit(room.id)}
                                                destroyRoute={rooms.destroy(
                                                    room.id,
                                                )}
                                                canManage={canManage}
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

RoomsIndex.layout = {
    breadcrumbs: [{ title: 'Kamar', href: rooms.index() }],
};
