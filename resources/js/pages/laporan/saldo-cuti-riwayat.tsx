import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes';
import {
    index as laporanIndex,
    saldoCuti as saldoCutiIndex,
} from '@/routes/laporan';
import type { PengajuanCuti, SaldoCuti } from '@/types';

type PageProps = {
    saldoCuti: SaldoCuti;
    pengajuans: PengajuanCuti[];
};

export default function LaporanSaldoCutiRiwayat() {
    const { saldoCuti, pengajuans } = usePage<PageProps>().props;

    const periode = saldoCuti.periode_ke
        ? `Periode ke-${saldoCuti.periode_ke} (${formatDate(saldoCuti.periode_mulai!)} s/d ${formatDate(saldoCuti.periode_selesai!)})`
        : `Tahun ${saldoCuti.tahun}`;

    return (
        <>
            <Head title="Riwayat Pemakaian Saldo Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <Link
                        href={saldoCutiIndex()}
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Kembali ke Saldo Cuti Karyawan
                    </Link>
                    <h1 className="mt-2 text-xl font-semibold">
                        {saldoCuti.karyawan?.nama} &middot;{' '}
                        {saldoCuti.jenis_cuti?.nama_jenis}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {periode} &middot; Kuota{' '}
                        {saldoCuti.kuota ?? 'tanpa batas'} hari, terpakai{' '}
                        {saldoCuti.terpakai} hari, sisa{' '}
                        {saldoCuti.sisa ?? 'tanpa batas'} hari
                    </p>
                </div>

                <Card>
                    <CardContent className="divide-y p-0">
                        {pengajuans.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Belum ada pemakaian pada periode ini.
                            </p>
                        )}
                        {pengajuans.map((pengajuan) => (
                            <div
                                key={pengajuan.id}
                                className="flex flex-col gap-1 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        {formatDate(pengajuan.tanggal_mulai)}{' '}
                                        s/d{' '}
                                        {formatDate(pengajuan.tanggal_selesai)}{' '}
                                        ({pengajuan.jumlah_hari} hari)
                                    </div>
                                    <div className="text-muted-foreground">
                                        {pengajuan.alasan}
                                    </div>
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    Diajukan{' '}
                                    {formatDateTime(
                                        pengajuan.tanggal_pengajuan,
                                    )}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

LaporanSaldoCutiRiwayat.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Laporan', href: laporanIndex() },
        { title: 'Saldo Cuti Karyawan', href: saldoCutiIndex() },
        { title: 'Riwayat Pemakaian', href: '#' },
    ],
};
