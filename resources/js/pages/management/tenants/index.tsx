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
import tenants from '@/routes/management/tenants';
import type { Paginated, TenantListItem } from '@/types';

type PageProps = {
    tenants: Paginated<TenantListItem>;
};

export default function TenantsIndex() {
    const { tenants: data } = usePage<PageProps>().props;
    const canManage = useCan('tenant.manage');

    return (
        <>
            <Head title="Bisnis" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Bisnis"
                    description="Kelola bisnis (tenant) beserta akun owner-nya."
                    action={
                        canManage
                            ? {
                                  label: 'Tambah Bisnis',
                                  href: tenants.create(),
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
                                    <TableHead>Cabang</TableHead>
                                    <TableHead>Pengguna</TableHead>
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
                                            colSpan={5}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada bisnis.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((tenant) => (
                                    <TableRow key={tenant.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {tenant.name}
                                            </div>
                                            {tenant.description && (
                                                <div className="max-w-md truncate text-xs text-muted-foreground">
                                                    {tenant.description}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {tenant.branches_count}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {tenant.users_count}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={tenant.status_label}
                                                active={
                                                    tenant.status === 'active'
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <RowActions
                                                label={tenant.name}
                                                editHref={tenants.edit(
                                                    tenant.id,
                                                )}
                                                destroyRoute={tenants.destroy(
                                                    tenant.id,
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

TenantsIndex.layout = {
    breadcrumbs: [{ title: 'Bisnis', href: tenants.index() }],
};
