import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { NativeSelect } from '@/components/ui/native-select';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import {
    create as cutiCreate,
    createMendadak as cutiCreateMendadak,
    index as cutiIndex,
    show as cutiShow,
} from '@/routes/cuti';
import type { Paginated, PengajuanCuti, StatusPengajuan } from '@/types';

type PageProps = {
    pengajuans: Paginated<PengajuanCuti>;
    filters: { status: StatusPengajuan | null; tahun: number | null };
    tahunTersedia: number[];
};

const OPSI_STATUS: { value: StatusPengajuan; label: string }[] = [
    { value: 'pending', label: 'Menunggu' },
    { value: 'disetujui', label: 'Disetujui' },
    { value: 'ditolak', label: 'Ditolak' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
];

export default function CutiIndex() {
    const { pengajuans, filters, tahunTersedia } = usePage<PageProps>().props;
    const adaFilter = filters.status !== null || filters.tahun !== null;

    const terapkanFilter = (perubahan: Partial<PageProps['filters']>) => {
        const berikut = { ...filters, ...perubahan };

        router.get(
            cutiIndex.url(),
            {
                status: berikut.status ?? undefined,
                tahun: berikut.tahun ?? undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

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

                <div className="flex flex-wrap gap-3">
                    <NativeSelect
                        wrapperClassName="w-full sm:w-44"
                        aria-label="Filter status"
                        value={filters.status ?? ''}
                        onChange={(e) =>
                            terapkanFilter({
                                status:
                                    (e.target.value as StatusPengajuan) || null,
                            })
                        }
                    >
                        <option value="">Semua Status</option>
                        {OPSI_STATUS.map((opsi) => (
                            <option key={opsi.value} value={opsi.value}>
                                {opsi.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <NativeSelect
                        wrapperClassName="w-full sm:w-36"
                        aria-label="Filter tahun"
                        value={filters.tahun ?? ''}
                        onChange={(e) =>
                            terapkanFilter({
                                tahun: e.target.value
                                    ? Number(e.target.value)
                                    : null,
                            })
                        }
                    >
                        <option value="">Semua Tahun</option>
                        {tahunTersedia.map((tahun) => (
                            <option key={tahun} value={tahun}>
                                {tahun}
                            </option>
                        ))}
                    </NativeSelect>
                    {adaFilter && (
                        <Button
                            variant="ghost"
                            onClick={() =>
                                terapkanFilter({ status: null, tahun: null })
                            }
                        >
                            Hapus filter
                        </Button>
                    )}
                </div>

                <Card className="gap-0 overflow-hidden py-0">
                    <CardContent className="divide-y p-0">
                        {pengajuans.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                {adaFilter
                                    ? 'Tidak ada pengajuan yang cocok dengan filter.'
                                    : 'Belum ada pengajuan cuti.'}
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

                <Pagination
                    links={pengajuans.links}
                    perPage={pengajuans.per_page}
                />
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
