import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { RowActions } from '@/components/master/row-actions';
import { StatusBadge } from '@/components/master/status-badge';
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
import roomTypes from '@/routes/room-types';
import type { Paginated, RoomTypeListItem } from '@/types';

type PageProps = {
    roomTypes: Paginated<RoomTypeListItem>;
};

export default function RoomTypesIndex() {
    const { roomTypes: data } = usePage<PageProps>().props;
    const canManage = useCanManageMaster();

    return (
        <>
            <Head title="Tipe Kamar" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Tipe Kamar"
                    description="Kelola tipe kamar beserta kapasitas dan harga dasarnya."
                    action={
                        canManage
                            ? {
                                  label: 'Tambah Tipe',
                                  href: roomTypes.create(),
                              }
                            : undefined
                    }
                />

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Kapasitas</TableHead>
                                    <TableHead>Harga / Bulan</TableHead>
                                    <TableHead>Kamar</TableHead>
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
                                            colSpan={6}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada tipe kamar.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((type) => (
                                    <TableRow key={type.id}>
                                        <TableCell className="font-medium">
                                            {type.name}
                                        </TableCell>
                                        <TableCell>
                                            {type.capacity} orang
                                        </TableCell>
                                        <TableCell>
                                            {formatRupiah(type.monthly_price)}
                                        </TableCell>
                                        <TableCell>
                                            {type.rooms_count}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={type.status_label}
                                                active={
                                                    type.status === 'active'
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <RowActions
                                                label={type.name}
                                                editHref={roomTypes.edit(
                                                    type.id,
                                                )}
                                                destroyRoute={roomTypes.destroy(
                                                    type.id,
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

RoomTypesIndex.layout = {
    breadcrumbs: [{ title: 'Tipe Kamar', href: roomTypes.index() }],
};
