import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import CutiMassalController from '@/actions/App/Http/Controllers/Cuti/CutiMassalController';
import { konfirmasi } from '@/components/confirm-dialog';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';
import { saldoSeverity } from '@/lib/saldo-severity';
import { dashboard } from '@/routes';
import { index as cutiMassalIndex } from '@/routes/cuti/massal';
import type {
    CutiMassal,
    KaryawanEligiblePreview,
    PengajuanCuti,
} from '@/types';

type PageProps = {
    cutiMassal: CutiMassal;
    karyawanBaru: KaryawanEligiblePreview[];
    penampungan: PengajuanCuti[];
};

export default function CutiMassalShow() {
    const { cutiMassal, karyawanBaru, penampungan } =
        usePage<PageProps>().props;
    const jumlahSiapDiproses = penampungan.filter(
        (pengajuan) => pengajuan.siap_diproses,
    ).length;
    const [prosesPenampunganProcessing, setProsesPenampunganProcessing] =
        useState(false);

    const prosesPenampungan = () => {
        setProsesPenampunganProcessing(true);
        router.post(
            CutiMassalController.prosesPenampungan.url(cutiMassal.id),
            {},
            { onFinish: () => setProsesPenampunganProcessing(false) },
        );
    };

    const { data, setData, patch, processing } = useForm({
        catatan_pembatalan: '',
    });

    const [terpilih, setTerpilih] = useState<Record<number, boolean>>({});
    const [susulProcessing, setSusulProcessing] = useState(false);

    const jumlahTerpilih = karyawanBaru.filter(
        (row) => terpilih[row.karyawan.id],
    ).length;
    const semuaTerpilih =
        karyawanBaru.length > 0 && jumlahTerpilih === karyawanBaru.length;

    const susulkanKaryawanBaru = () => {
        const ids = karyawanBaru
            .filter((row) => terpilih[row.karyawan.id])
            .map((row) => row.karyawan.id);

        if (ids.length === 0) {
            return;
        }

        setSusulProcessing(true);
        router.post(
            CutiMassalController.tambahKaryawanBaru.url(cutiMassal.id),
            { karyawan_ids: ids },
            { onFinish: () => setSusulProcessing(false) },
        );
    };

    const batalkan = async (e: React.FormEvent) => {
        e.preventDefault();

        if (
            !(await konfirmasi({
                title: 'Batalkan cuti massal ini?',
                description:
                    'Saldo cuti seluruh karyawan terdampak akan dikembalikan.',
                confirmText: 'Batalkan Cuti Massal',
                cancelText: 'Kembali',
                destructive: true,
            }))
        ) {
            return;
        }

        patch(CutiMassalController.batalkan.url(cutiMassal.id));
    };

    return (
        <>
            <Head title="Detail Cuti Massal" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">
                        {cutiMassal.jenis_cuti?.nama_jenis}
                    </h1>
                    <Badge
                        variant={
                            cutiMassal.status === 'aktif'
                                ? 'secondary'
                                : 'destructive'
                        }
                    >
                        {cutiMassal.status === 'aktif' ? 'Aktif' : 'Dibatalkan'}
                    </Badge>
                </div>

                <Card className="max-w-3xl">
                    <CardContent className="space-y-1 text-sm">
                        <p>
                            {formatDate(cutiMassal.tanggal_mulai)} s/d{' '}
                            {formatDate(cutiMassal.tanggal_selesai)} (
                            {cutiMassal.jumlah_hari} hari kerja)
                        </p>
                        <p className="text-muted-foreground">
                            Alasan: {cutiMassal.alasan}
                        </p>
                        <p className="text-muted-foreground">
                            Dibuat oleh {cutiMassal.dibuat_oleh?.nama} untuk{' '}
                            {cutiMassal.jumlah_karyawan} karyawan
                        </p>
                        {cutiMassal.status === 'dibatalkan' && (
                            <p className="text-muted-foreground">
                                Dibatalkan oleh{' '}
                                {cutiMassal.dibatalkan_oleh?.nama} pada{' '}
                                {cutiMassal.dibatalkan_pada &&
                                    formatDate(cutiMassal.dibatalkan_pada)}
                                {cutiMassal.catatan_pembatalan &&
                                    ` — ${cutiMassal.catatan_pembatalan}`}
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card className="max-w-3xl gap-0 overflow-hidden py-0">
                    <CardContent className="space-y-2 p-0">
                        <h2 className="px-4 pt-4 text-sm font-semibold">
                            Karyawan Diproses ({cutiMassal.jumlah_karyawan})
                        </h2>
                        <div className="divide-y">
                            {cutiMassal.pengajuan_cutis?.map((pengajuan) => (
                                <div
                                    key={pengajuan.id}
                                    className="flex items-center justify-between gap-2 px-4 py-2 text-sm"
                                >
                                    <span>
                                        {pengajuan.karyawan?.nama}
                                        {pengajuan.jenis_cuti_id !==
                                            cutiMassal.jenis_cuti_id && (
                                            <span className="text-muted-foreground">
                                                {' '}
                                                — {pengajuan.jumlah_hari} hari
                                                dari{' '}
                                                {
                                                    pengajuan.jenis_cuti
                                                        ?.nama_jenis
                                                }
                                            </span>
                                        )}
                                    </span>
                                    {pengajuan.status === 'ditampung' && (
                                        <StatusBadge
                                            status={pengajuan.status}
                                        />
                                    )}
                                </div>
                            ))}
                            {(!cutiMassal.pengajuan_cutis ||
                                cutiMassal.pengajuan_cutis.length === 0) && (
                                <p className="px-4 pb-4 text-sm text-muted-foreground">
                                    Tidak ada karyawan yang diproses.
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {penampungan.length > 0 && (
                    <Card className="max-w-3xl gap-0 overflow-hidden py-0">
                        <CardContent className="space-y-3 p-0">
                            <div className="px-4 pt-4">
                                <h2 className="text-sm font-semibold">
                                    Ditampung ({penampungan.length})
                                </h2>
                                <p className="text-xs text-muted-foreground">
                                    Periode saldo karyawan ini berakhir sebelum
                                    tanggal cuti massal, jadi saldonya belum
                                    dipotong. Proses setelah periode saldo
                                    barunya aktif — HRD akan mendapat notifikasi
                                    saat itu.
                                </p>
                            </div>
                            <div className="divide-y border-t">
                                {penampungan.map((pengajuan) => (
                                    <div
                                        key={pengajuan.id}
                                        className="flex items-center justify-between gap-2 px-4 py-2 text-sm"
                                    >
                                        {pengajuan.karyawan?.nama}
                                        {pengajuan.siap_diproses ? (
                                            <Badge className="border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300">
                                                Siap diproses
                                            </Badge>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                Menunggu periode baru
                                            </span>
                                        )}
                                    </div>
                                ))}
                            </div>
                            <div className="px-4 pb-4">
                                <Button
                                    size="sm"
                                    disabled={
                                        prosesPenampunganProcessing ||
                                        jumlahSiapDiproses === 0
                                    }
                                    onClick={prosesPenampungan}
                                >
                                    Proses {jumlahSiapDiproses} yang Siap
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {cutiMassal.dilewati && cutiMassal.dilewati.length > 0 && (
                    <Card className="max-w-3xl gap-0 overflow-hidden py-0">
                        <CardContent className="space-y-2 p-0">
                            <h2 className="px-4 pt-4 text-sm font-semibold">
                                Dilewati ({cutiMassal.dilewati.length})
                            </h2>
                            <div className="divide-y">
                                {cutiMassal.dilewati.map((item) => (
                                    <div
                                        key={item.karyawan_id}
                                        className="px-4 py-2 text-sm"
                                    >
                                        <div className="font-medium">
                                            {item.nama}
                                        </div>
                                        <div className="text-muted-foreground">
                                            {item.alasan}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {cutiMassal.status === 'aktif' && karyawanBaru.length > 0 && (
                    <Card className="max-w-3xl gap-0 overflow-hidden py-0">
                        <CardContent className="space-y-3 p-0">
                            <div className="flex items-center justify-between gap-2 px-4 pt-4">
                                <div>
                                    <h2 className="text-sm font-semibold">
                                        Karyawan Baru yang Belum Disertakan (
                                        {karyawanBaru.length})
                                    </h2>
                                    <p className="text-xs text-muted-foreground">
                                        Karyawan yang masuk kerja setelah batch
                                        ini dibuat. Pilih untuk disertakan
                                        dengan jenis cuti, tanggal, dan alasan
                                        yang sama.
                                    </p>
                                </div>
                            </div>
                            <div className="divide-y border-t">
                                <label className="flex cursor-pointer items-center gap-3 bg-muted/50 px-4 py-2 text-sm">
                                    <Checkbox
                                        checked={semuaTerpilih}
                                        onCheckedChange={(checked) =>
                                            setTerpilih(
                                                Object.fromEntries(
                                                    karyawanBaru.map((row) => [
                                                        row.karyawan.id,
                                                        Boolean(checked),
                                                    ]),
                                                ),
                                            )
                                        }
                                    />
                                    <span className="font-medium">
                                        Pilih semua
                                    </span>
                                </label>
                                {karyawanBaru.map((row) => (
                                    <label
                                        key={row.karyawan.id}
                                        className="flex cursor-pointer items-center justify-between gap-3 px-4 py-2 text-sm hover:bg-accent/50"
                                    >
                                        <div className="flex items-center gap-3">
                                            <Checkbox
                                                checked={
                                                    !!terpilih[row.karyawan.id]
                                                }
                                                onCheckedChange={(checked) =>
                                                    setTerpilih((prev) => ({
                                                        ...prev,
                                                        [row.karyawan.id]:
                                                            Boolean(checked),
                                                    }))
                                                }
                                            />
                                            <span>{row.karyawan.nama}</span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span
                                                className={
                                                    row.saldo
                                                        ? saldoSeverity(
                                                              row.saldo.sisa,
                                                              row.saldo.kuota,
                                                          ).text
                                                        : 'text-muted-foreground'
                                                }
                                            >
                                                {row.saldo
                                                    ? row.saldo.kuota === null
                                                        ? 'sisa tanpa batas'
                                                        : `sisa ${row.saldo.sisa} hari`
                                                    : 'tidak ada saldo aktif'}
                                            </span>
                                            {row.akan_minus && (
                                                <Badge variant="destructive">
                                                    Akan minus
                                                </Badge>
                                            )}
                                            {row.akan_dapat_bonus && (
                                                <Badge className="border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300">
                                                    Dapat bonus cuti
                                                </Badge>
                                            )}
                                            {row.dari_cuti_besar > 0 && (
                                                <Badge className="border-violet-200 bg-violet-100 text-violet-800 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-300">
                                                    {row.dari_cuti_besar} hari
                                                    dari Cuti Besar
                                                </Badge>
                                            )}
                                            {row.akan_ditampung && (
                                                <Badge className="border-sky-200 bg-sky-100 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300">
                                                    Akan ditampung
                                                </Badge>
                                            )}
                                        </div>
                                    </label>
                                ))}
                            </div>
                            <div className="px-4 pb-4">
                                <Button
                                    size="sm"
                                    disabled={
                                        susulProcessing || jumlahTerpilih === 0
                                    }
                                    onClick={susulkanKaryawanBaru}
                                >
                                    Sertakan {jumlahTerpilih} Karyawan Terpilih
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {cutiMassal.status === 'aktif' && (
                    <Card className="max-w-3xl">
                        <CardContent>
                            <form onSubmit={batalkan} className="space-y-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="catatan_pembatalan">
                                        Catatan Pembatalan (opsional)
                                    </Label>
                                    <Textarea
                                        id="catatan_pembatalan"
                                        maxLength={500}
                                        value={data.catatan_pembatalan}
                                        onChange={(e) =>
                                            setData(
                                                'catatan_pembatalan',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Batalkan Batch
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

CutiMassalShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Cuti Massal', href: cutiMassalIndex() },
        { title: 'Detail', href: '#' },
    ],
};
