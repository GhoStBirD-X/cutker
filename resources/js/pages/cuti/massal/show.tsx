import { Head, useForm, usePage } from '@inertiajs/react';
import CutiMassalController from '@/actions/App/Http/Controllers/Cuti/CutiMassalController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as cutiMassalIndex } from '@/routes/cuti/massal';
import type { CutiMassal } from '@/types';

type PageProps = {
    cutiMassal: CutiMassal;
};

export default function CutiMassalShow() {
    const { cutiMassal } = usePage<PageProps>().props;

    const { data, setData, patch, processing } = useForm({
        catatan_pembatalan: '',
    });

    const batalkan = (e: React.FormEvent) => {
        e.preventDefault();

        if (
            !confirm(
                'Batalkan cuti massal ini? Saldo cuti seluruh karyawan terdampak akan dikembalikan.',
            )
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

                <Card className="max-w-3xl">
                    <CardContent className="space-y-2 p-0">
                        <h2 className="px-4 pt-4 text-sm font-semibold">
                            Karyawan Diproses ({cutiMassal.jumlah_karyawan})
                        </h2>
                        <div className="divide-y">
                            {cutiMassal.pengajuan_cutis?.map((pengajuan) => (
                                <div
                                    key={pengajuan.id}
                                    className="px-4 py-2 text-sm"
                                >
                                    {pengajuan.karyawan?.nama}
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

                {cutiMassal.dilewati && cutiMassal.dilewati.length > 0 && (
                    <Card className="max-w-3xl">
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
