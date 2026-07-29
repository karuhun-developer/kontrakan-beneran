import { Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/master/page-header';
import { Pagination } from '@/components/master/pagination';
import { RowActions } from '@/components/master/row-actions';
import { ColorBadge, StatusBadge } from '@/components/master/status-badge';
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
import cashflowCategories from '@/routes/cashflow-categories';
import cashflows from '@/routes/cashflows';
import type { CashflowCategoryListItem, Paginated } from '@/types/master';

type PageProps = {
    categories: Paginated<CashflowCategoryListItem>;
};

export default function CashflowCategoriesIndex() {
    const { categories: data } = usePage<PageProps>().props;
    const canManage = useCan('cashflow.manage');

    return (
        <>
            <Head title="Kategori Kas" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Kategori Kas"
                    description="Kelompokkan pemasukan & pengeluaran agar laporan lebih rapi."
                    action={
                        canManage
                            ? {
                                  label: 'Tambah Kategori',
                                  href: cashflowCategories.create(),
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
                                    <TableHead>Tipe</TableHead>
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
                                            Belum ada kategori.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {data.data.map((category) => (
                                    <TableRow key={category.id}>
                                        <TableCell className="font-medium">
                                            {category.name}
                                            {category.is_system && (
                                                <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                    (sistem)
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <ColorBadge
                                                label={category.type_label}
                                                color={category.type_color}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={category.status_label}
                                                active={
                                                    category.status === 'active'
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            {category.is_system ? (
                                                <span className="block text-right text-xs text-muted-foreground">
                                                    —
                                                </span>
                                            ) : (
                                                <RowActions
                                                    label={category.name}
                                                    editHref={cashflowCategories.edit(
                                                        category.id,
                                                    )}
                                                    destroyRoute={cashflowCategories.destroy(
                                                        category.id,
                                                    )}
                                                    canManage={canManage}
                                                />
                                            )}
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

CashflowCategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Kas', href: cashflows.index() },
        { title: 'Kategori', href: cashflowCategories.index() },
    ],
};
