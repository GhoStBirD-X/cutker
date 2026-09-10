import { Form, Head, usePage } from '@inertiajs/react';
import PengajuanCutiController from '@/actions/App/Http/Controllers/Cuti/PengajuanCutiController';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { approvalLevelLabel, formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as cutiIndex } from '@/routes/cuti';
import type { Auth, PengajuanCuti } from '@/types';

type PageProps = {
    auth: Auth;
    pengajuan: PengajuanCuti;
};

export default function CutiShow() {
    const { auth, pengajuan } = usePage<PageProps>().props;

    const bisaBatalkan =
        pengajuan.status === 'pending' &&
        pengajuan.karyawan_id === auth.user.karyawan_id;

    return (
        <>
            <Head title="Detail Pengajuan Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {pengajuan.jenis_cuti?.nama_jenis}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Diajukan oleh {pengajuan.karyawan?.nama}
                        </p>
                    </div>
                    <StatusBadge status={pengajuan.status} />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Detail Pengajuan</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div className="text-muted-foreground">
                                Tanggal Mulai
                            </div>
                            <div>{formatDate(pengajuan.tanggal_mulai)}</div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Tanggal Selesai
                            </div>
                            <div>{formatDate(pengajuan.tanggal_selesai)}</div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Jumlah Hari
                            </div>
                            <div>
                                {pengajuan.jumlah_hari} hari
                                {pengajuan.jumlah_hari_kalender >
                                    pengajuan.jumlah_hari && (
                                    <span className="text-xs text-muted-foreground">
                                        {' '}
                                        (dari {
                                            pengajuan.jumlah_hari_kalender
                                        }{' '}
                                        hari kalender, dikurangi{' '}
                                        {pengajuan.jumlah_hari_kalender -
                                            pengajuan.jumlah_hari}{' '}
                                        hari libur nasional)
                                    </span>
                                )}
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Tanggal Pengajuan
                            </div>
                            <div>
                                {formatDateTime(pengajuan.tanggal_pengajuan)}
                            </div>
                        </div>
                        <div className="col-span-2">
                            <div className="text-muted-foreground">Alasan</div>
                            <div>{pengajuan.alasan}</div>
                        </div>
                        {pengajuan.lampiran && (
                            <div className="col-span-2">
                                <div className="text-muted-foreground">
                                    Lampiran
                                </div>
                                <a
                                    href={`/storage/${pengajuan.lampiran}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-primary underline"
                                >
                                    Lihat lampiran
                                </a>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Riwayat Approval</CardTitle>
                    </CardHeader>
                    <CardContent className="divide-y p-0">
                        {(pengajuan.approvals ?? []).map((approval) => (
                            <div
                                key={approval.id}
                                className="flex flex-col gap-2 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        Level {approval.level} (
                                        {approvalLevelLabel(approval.level)})
                                        &middot;{' '}
                                        {approval.approver?.nama ??
                                            'Belum ditentukan'}
                                    </div>
                                    {approval.catatan && (
                                        <div className="text-muted-foreground">
                                            Catatan: {approval.catatan}
                                        </div>
                                    )}
                                </div>
                                <StatusBadge status={approval.status} />
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {bisaBatalkan && (
                    <Form
                        {...PengajuanCutiController.batalkan.form(pengajuan.id)}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                                className="w-fit"
                            >
                                Batalkan Pengajuan
                            </Button>
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

CutiShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Riwayat Cuti', href: cutiIndex() },
        { title: 'Detail', href: '#' },
    ],
};
