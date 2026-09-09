import { Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import { show as cutiShow } from '@/routes/cuti';
import { index as kompensasiIndex } from '@/routes/cuti/kompensasi';
import { index as konfirmasiKontrakIndex } from '@/routes/cuti/konfirmasi-kontrak';
import type { Auth, PengajuanCuti, RiwayatSaldoCuti, SaldoCuti } from '@/types';

type RingkasanPabrik = {
    total_karyawan_aktif: number;
    total_pengajuan_pending: number;
    total_pengajuan_bulan_ini: number;
};

type PageProps = {
    auth: Auth;
    saldoCuti?: SaldoCuti[];
    riwayatCuti?: PengajuanCuti[];
    resumeCuti?: RiwayatSaldoCuti[];
    menungguKonfirmasiKontrak?: boolean;
    approvalPendingCount?: number;
    ringkasanPabrik?: RingkasanPabrik;
    konfirmasiKontrakPendingCount?: number;
    kompensasiCutiPendingCount?: number;
};

export default function Dashboard() {
    const {
        auth,
        saldoCuti,
        riwayatCuti,
        resumeCuti,
        menungguKonfirmasiKontrak,
        approvalPendingCount,
        ringkasanPabrik,
        konfirmasiKontrakPendingCount,
        kompensasiCutiPendingCount,
    } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        Halo, {auth.user.karyawan?.nama ?? auth.user.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Ringkasan cuti dan aktivitas Anda.
                    </p>
                </div>

                {menungguKonfirmasiKontrak && (
                    <Card className="border-amber-500">
                        <CardContent className="text-sm">
                            Kontrak kerja Anda sedang menunggu konfirmasi
                            perpanjangan dari HRD. Saldo cuti tahunan Anda baru
                            akan direset setelah konfirmasi selesai.
                        </CardContent>
                    </Card>
                )}

                {ringkasanPabrik && (
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Karyawan Aktif
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-2xl font-bold">
                                {ringkasanPabrik.total_karyawan_aktif}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Pengajuan Pending
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-2xl font-bold">
                                {ringkasanPabrik.total_pengajuan_pending}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Pengajuan Bulan Ini
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-2xl font-bold">
                                {ringkasanPabrik.total_pengajuan_bulan_ini}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {(typeof approvalPendingCount === 'number' ||
                    typeof konfirmasiKontrakPendingCount === 'number' ||
                    typeof kompensasiCutiPendingCount === 'number') && (
                    <div className="grid gap-4 sm:grid-cols-3">
                        {typeof approvalPendingCount === 'number' && (
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        Approval Menunggu Tindakan Anda
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="text-2xl font-bold">
                                    {approvalPendingCount}
                                </CardContent>
                            </Card>
                        )}
                        {typeof konfirmasiKontrakPendingCount === 'number' && (
                            <Link href={konfirmasiKontrakIndex()}>
                                <Card className="transition-colors hover:bg-accent">
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-sm font-medium text-muted-foreground">
                                            Menunggu Konfirmasi Kontrak
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="text-2xl font-bold">
                                        {konfirmasiKontrakPendingCount}
                                    </CardContent>
                                </Card>
                            </Link>
                        )}
                        {typeof kompensasiCutiPendingCount === 'number' && (
                            <Link href={kompensasiIndex()}>
                                <Card className="transition-colors hover:bg-accent">
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-sm font-medium text-muted-foreground">
                                            Kompensasi Cuti Menunggu Diproses
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="text-2xl font-bold">
                                        {kompensasiCutiPendingCount}
                                    </CardContent>
                                </Card>
                            </Link>
                        )}
                    </div>
                )}

                {saldoCuti && (
                    <div>
                        <h2 className="mb-2 text-sm font-semibold text-muted-foreground">
                            Sisa Saldo Cuti Berjalan
                        </h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {saldoCuti.map((saldo) => (
                                <Card key={saldo.id}>
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-sm font-medium">
                                            {saldo.jenis_cuti?.nama_jenis}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">
                                            {saldo.sisa} hari
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            dari kuota {saldo.kuota} hari,
                                            terpakai {saldo.terpakai}
                                        </p>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>
                )}

                {riwayatCuti && (
                    <div>
                        <h2 className="mb-2 text-sm font-semibold text-muted-foreground">
                            Riwayat Pengajuan Terbaru
                        </h2>
                        <Card>
                            <CardContent className="divide-y p-0">
                                {riwayatCuti.length === 0 && (
                                    <p className="p-4 text-sm text-muted-foreground">
                                        Belum ada pengajuan cuti.
                                    </p>
                                )}
                                {riwayatCuti.map((pengajuan) => (
                                    <Link
                                        key={pengajuan.id}
                                        href={cutiShow(pengajuan.id)}
                                        className="flex items-center justify-between p-4 text-sm hover:bg-accent"
                                    >
                                        <div>
                                            <div className="font-medium">
                                                {
                                                    pengajuan.jenis_cuti
                                                        ?.nama_jenis
                                                }
                                            </div>
                                            <div className="text-muted-foreground">
                                                {formatDate(
                                                    pengajuan.tanggal_mulai,
                                                )}{' '}
                                                s/d{' '}
                                                {formatDate(
                                                    pengajuan.tanggal_selesai,
                                                )}{' '}
                                                ({pengajuan.jumlah_hari} hari)
                                            </div>
                                        </div>
                                        <StatusBadge
                                            status={pengajuan.status}
                                        />
                                    </Link>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {resumeCuti && resumeCuti.length > 0 && (
                    <div>
                        <h2 className="mb-2 text-sm font-semibold text-muted-foreground">
                            Resume Cuti Tahunan
                        </h2>
                        <Card>
                            <CardContent className="divide-y p-0">
                                {resumeCuti.map((riwayat) => (
                                    <div
                                        key={riwayat.id}
                                        className="flex items-center justify-between p-4 text-sm"
                                    >
                                        <div>
                                            <div className="font-medium">
                                                {riwayat.jenis_cuti?.nama_jenis}{' '}
                                                &middot; Periode ke-
                                                {riwayat.periode_ke}
                                            </div>
                                            <div className="text-muted-foreground">
                                                {formatDate(
                                                    riwayat.periode_mulai,
                                                )}{' '}
                                                s/d{' '}
                                                {formatDate(
                                                    riwayat.periode_selesai,
                                                )}
                                            </div>
                                        </div>
                                        <Badge variant="outline">
                                            Terpakai {riwayat.terpakai}/
                                            {riwayat.kuota} hari
                                        </Badge>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
