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
import { useCan } from '@/hooks/use-can';
import branches from '@/routes/management/branches';
import type { BranchListItem, Paginated } from '@/types';

type PageProps = {
    branches: Paginated<BranchListItem>;
};

export default function BranchesIndex() {
    const { branches: data } = usePage<PageProps>().props;
    const canManage = useCan('branch.manage');

    return (
        <>
            <Head title="Cabang" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Cabang"
                    description="Kelola cabang beserta alamat dan rekening transfer-nya."
                    action={
                        canManage
                            ? {
                                  label: 'Tambah Cabang',
                                  href: branches.create(),
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
                                    <TableHead>Kontak</TableHead>
                                    <TableHead>Rekening</TableHead>
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
                                            Belum ada cabang.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((branch) => (
                                    <TableRow key={branch.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {branch.name}
                                            </div>
                                            {branch.address && (
                                                <div className="max-w-xs truncate text-xs text-muted-foreground">
                                                    {branch.address}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {branch.phone ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {branch.bank_name ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {branch.rooms_count}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={branch.status_label}
                                                active={
                                                    branch.status === 'active'
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <RowActions
                                                label={branch.name}
                                                editHref={branches.edit(
                                                    branch.id,
                                                )}
                                                destroyRoute={branches.destroy(
                                                    branch.id,
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

BranchesIndex.layout = {
    breadcrumbs: [{ title: 'Cabang', href: branches.index() }],
};
