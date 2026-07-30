import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { RowActions } from '@/components/master/row-actions';
import { Badge } from '@/components/ui/badge';
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
import users from '@/routes/management/users';
import type { ManagedUserListItem, Paginated } from '@/types';

type PageProps = {
    users: Paginated<ManagedUserListItem>;
    canViewTenants: boolean;
    canCreate: boolean;
};

export default function UsersIndex() {
    const {
        users: data,
        canViewTenants,
        canCreate,
    } = usePage<PageProps>().props;
    const canManage = useCan('tenant.member.manage');
    const columnCount = canViewTenants ? 5 : 4;

    return (
        <>
            <Head title="Pengguna" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Pengguna"
                    description="Kelola akun owner, admin cabang, dan staff beserta penugasan cabangnya."
                    action={
                        canManage && canCreate
                            ? {
                                  label: 'Tambah Pengguna',
                                  href: users.create(),
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
                                    <TableHead>Peran</TableHead>
                                    {canViewTenants && (
                                        <TableHead>Bisnis</TableHead>
                                    )}
                                    <TableHead>Cabang</TableHead>
                                    <TableHead className="text-right">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={columnCount}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            Belum ada pengguna.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {user.name}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {user.email}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {user.role_label && (
                                                <Badge
                                                    variant={
                                                        user.is_owner
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {user.role_label}
                                                </Badge>
                                            )}
                                        </TableCell>
                                        {canViewTenants && (
                                            <TableCell>
                                                <span className="text-sm">
                                                    {user.tenant ?? '—'}
                                                </span>
                                            </TableCell>
                                        )}
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {user.branches.length === 0 ? (
                                                    <span className="text-xs text-muted-foreground">
                                                        —
                                                    </span>
                                                ) : (
                                                    user.branches.map(
                                                        (branch) => (
                                                            <Badge
                                                                key={branch}
                                                                variant="outline"
                                                            >
                                                                {branch}
                                                            </Badge>
                                                        ),
                                                    )
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <RowActions
                                                label={user.name}
                                                editHref={users.edit(user.id)}
                                                destroyRoute={users.destroy(
                                                    user.id,
                                                )}
                                                canManage={canManage}
                                                canDelete={!user.is_owner}
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

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Pengguna', href: users.index() }],
};
