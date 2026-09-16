import { Head, Link, usePage } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import {
    create as cutiMassalCreate,
    index as cutiMassalIndex,
    show as cutiMassalShow,
} from '@/routes/cuti/massal';
import type { CutiMassal, Paginated } from '@/types';

type PageProps = {
    cutiMassals: Paginated<CutiMassal>;
};

export default function CutiMassalIndex() {
    const { cutiMassals } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Cuti Massal" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="flex items-center gap-2 text-xl font-semibold">
                        <Users className="size-5 text-primary" />
                        Cuti Massal
                    </h1>
                    <Button asChild>
                        <Link href={cutiMassalCreate()}>Buat Cuti Massal</Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="divide-y p-0">
                        {cutiMassals.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Belum ada cuti massal yang dibuat.
                            </p>
                        )}
                        {cutiMassals.data.map((cutiMassal) => (
                            <Link
                                key={cutiMassal.id}
                                href={cutiMassalShow.url(cutiMassal.id)}
                                className="flex flex-col gap-2 p-4 text-sm transition-colors hover:bg-accent/50 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        {cutiMassal.jenis_cuti?.nama_jenis}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {formatDate(cutiMassal.tanggal_mulai)}{' '}
                                        s/d{' '}
                                        {formatDate(cutiMassal.tanggal_selesai)}{' '}
                                        &middot; {cutiMassal.jumlah_karyawan}{' '}
                                        karyawan &middot; dibuat oleh{' '}
                                        {cutiMassal.dibuat_oleh?.nama}
                                    </div>
                                </div>
                                <Badge
                                    variant={
                                        cutiMassal.status === 'aktif'
                                            ? 'secondary'
                                            : 'destructive'
                                    }
                                >
                                    {cutiMassal.status === 'aktif'
                                        ? 'Aktif'
                                        : 'Dibatalkan'}
                                </Badge>
                            </Link>
                        ))}
                    </CardContent>
                </Card>

                <Pagination
                    links={cutiMassals.links}
                    perPage={cutiMassals.per_page}
                />
            </div>
        </>
    );
}

CutiMassalIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Cuti Massal', href: cutiMassalIndex() },
    ],
};
