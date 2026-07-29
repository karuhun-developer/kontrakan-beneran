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
import facilities from '@/routes/facilities';
import type { FacilityListItem, Paginated } from '@/types';

type PageProps = {
    facilities: Paginated<FacilityListItem>;
};

export default function FacilitiesIndex() {
    const { facilities: data } = usePage<PageProps>().props;
    const canManage = useCanManageMaster();

    return (
        <>
            <Head title="Fasilitas" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Fasilitas"
                    description="Kelola daftar fasilitas yang dapat dilekatkan ke kamar."
                    action={
                        canManage
                            ? {
                                  label: 'Tambah Fasilitas',
                                  href: facilities.create(),
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
                                    <TableHead>Deskripsi</TableHead>
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
                                            colSpan={4}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada fasilitas.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((facility) => (
                                    <TableRow key={facility.id}>
                                        <TableCell className="font-medium">
                                            {facility.name}
                                        </TableCell>
                                        <TableCell className="max-w-md truncate text-muted-foreground">
                                            {facility.description ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={facility.status_label}
                                                active={
                                                    facility.status === 'active'
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <RowActions
                                                label={facility.name}
                                                editHref={facilities.edit(
                                                    facility.id,
                                                )}
                                                destroyRoute={facilities.destroy(
                                                    facility.id,
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

FacilitiesIndex.layout = {
    breadcrumbs: [{ title: 'Fasilitas', href: facilities.index() }],
};
