import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { RowActions } from '@/components/master/row-actions';
import { StatusBadge } from '@/components/master/status-badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { useInitials } from '@/hooks/use-initials';
import penyewa from '@/routes/penyewa';
import type { Paginated, PenyewaListItem } from '@/types';

type PageProps = {
    penyewa: Paginated<PenyewaListItem>;
};

const genderLabels: Record<string, string> = {
    male: 'Laki-laki',
    female: 'Perempuan',
};

export default function PenyewaIndex() {
    const { penyewa: data } = usePage<PageProps>().props;
    const canManage = useCanManageMaster();
    const getInitials = useInitials();

    return (
        <>
            <Head title="Penyewa" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Penyewa"
                    description="Kelola data penyewa beserta identitas dan kontaknya."
                    action={
                        canManage
                            ? {
                                  label: 'Tambah Penyewa',
                                  href: penyewa.create(),
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
                                    <TableHead>Gender</TableHead>
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
                                            Belum ada penyewa.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <Avatar className="size-9 rounded-md">
                                                    {item.id_card_url && (
                                                        <AvatarImage
                                                            src={
                                                                item.id_card_url
                                                            }
                                                            alt={item.name}
                                                        />
                                                    )}
                                                    <AvatarFallback className="rounded-md">
                                                        {getInitials(item.name)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <span className="font-medium">
                                                    {item.name}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col text-sm">
                                                <span>{item.phone ?? '—'}</span>
                                                <span className="text-muted-foreground">
                                                    {item.email ?? '—'}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {item.gender
                                                ? (genderLabels[item.gender] ??
                                                  item.gender)
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={item.status_label}
                                                active={
                                                    item.status === 'active'
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <RowActions
                                                label={item.name}
                                                editHref={penyewa.edit(item.id)}
                                                destroyRoute={penyewa.destroy(
                                                    item.id,
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

PenyewaIndex.layout = {
    breadcrumbs: [{ title: 'Penyewa', href: penyewa.index() }],
};
