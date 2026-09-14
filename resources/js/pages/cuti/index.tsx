import { Head, Link, usePage } from '@inertiajs/react';
import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import {
    create as cutiCreate,
    createMendadak as cutiCreateMendadak,
    index as cutiIndex,
    show as cutiShow,
} from '@/routes/cuti';
import type { Paginated, PengajuanCuti } from '@/types';

type PageProps = {
    pengajuans: Paginated<PengajuanCuti>;
};

export default function CutiIndex() {
    const { pengajuans } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Riwayat Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">
                        Riwayat Pengajuan Cuti
                    </h1>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={cutiCreateMendadak()}>
                                Ajukan Mendadak
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={cutiCreate()}>Ajukan Cuti</Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardContent className="divide-y p-0">
                        {pengajuans.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Belum ada pengajuan cuti.
                            </p>
                        )}
                        {pengajuans.data.map((pengajuan) => (
                            <Link
                                key={pengajuan.id}
                                href={cutiShow(pengajuan.id)}
                                className="flex flex-col gap-2 p-4 text-sm hover:bg-accent sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        {pengajuan.jenis_cuti?.nama_jenis}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {formatDate(pengajuan.tanggal_mulai)}{' '}
                                        s/d{' '}
                                        {formatDate(pengajuan.tanggal_selesai)}{' '}
                                        ({pengajuan.jumlah_hari} hari)
                                    </div>
                                </div>
                                <StatusBadge status={pengajuan.status} />
                            </Link>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={pengajuans.links} />
            </div>
        </>
    );
}

CutiIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Riwayat Cuti', href: cutiIndex() },
    ],
};
