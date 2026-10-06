import { Head, useForm, usePage } from '@inertiajs/react';
import {
    CheckSquare,
    FileText,
    History,
    Paperclip,
    Users,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import ApprovalController from '@/actions/App/Http/Controllers/Approval/ApprovalController';
import { konfirmasi } from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { RiwayatApprovalList } from '@/components/riwayat-approval-list';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { approvalLevelLabel, formatDate } from '@/lib/format';
import { saldoSeverity } from '@/lib/saldo-severity';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as approvalIndex } from '@/routes/approval';
import type { Approval, PengajuanCuti, StatusPengajuan } from '@/types';

type RekanCuti = Pick<
    PengajuanCuti,
    'id' | 'tanggal_mulai' | 'tanggal_selesai' | 'karyawan' | 'jenis_cuti'
> & { status: StatusPengajuan };

type PageProps = {
    approval: Approval;
    canAct: boolean;
    saldoKaryawan: {
        kuota: number | null;
        terpakai: number;
        sisa: number | null;
    } | null;
    rekanCutiBersamaan: RekanCuti[];
};

export default function ApprovalShow() {
    const { approval, canAct, saldoKaryawan, rekanCutiBersamaan } =
        usePage<PageProps>().props;
    const pengajuan = approval.pengajuan_cuti;
    const { data, setData, post, processing, errors, setError, clearErrors } =
        useForm({
            catatan: '',
        });
    const [aksiBerjalan, setAksiBerjalan] = useState<
        'approve' | 'reject' | null
    >(null);

    const submit = async (aksi: 'approve' | 'reject') => {
        const tolak = aksi === 'reject';

        if (tolak && data.catatan.trim() === '') {
            setError(
                'catatan',
                'Catatan wajib diisi saat menolak, agar karyawan tahu alasannya.',
            );

            return;
        }

        clearErrors();

        const yakin = await konfirmasi({
            title: tolak
                ? 'Tolak pengajuan cuti ini?'
                : 'Setujui pengajuan cuti ini?',
            description: tolak
                ? `Pengajuan ${pengajuan?.karyawan?.nama} akan ditolak dan alur approval berhenti. Keputusan tidak bisa diubah.`
                : `Pengajuan ${pengajuan?.karyawan?.nama} (${pengajuan?.jumlah_hari} hari) akan disetujui dan diteruskan ke level berikutnya bila ada. Keputusan tidak bisa diubah.`,
            confirmText: tolak ? 'Tolak' : 'Setujui',
            destructive: tolak,
        });

        if (!yakin) {
            return;
        }

        const action = tolak
            ? ApprovalController.reject
            : ApprovalController.approve;
        setAksiBerjalan(aksi);
        post(action.url(approval.id), {
            onFinish: () => setAksiBerjalan(null),
        });
    };

    const sisaSetelahDisetujui =
        saldoKaryawan?.sisa != null && pengajuan
            ? saldoKaryawan.sisa - pengajuan.jumlah_hari
            : null;

    return (
        <>
            <Head title="Detail Approval" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {pengajuan?.karyawan?.nama}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {pengajuan?.karyawan?.departemen?.nama_departemen}{' '}
                            &middot; Level {approval.level} (
                            {approvalLevelLabel(approval.level)})
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {pengajuan?.is_mendadak && (
                            <Badge
                                variant="outline"
                                className="border-amber-200 bg-amber-100 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300"
                            >
                                Mendadak
                            </Badge>
                        )}
                        <StatusBadge status={approval.status} />
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="size-4 text-muted-foreground" />
                            Detail Pengajuan
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div className="text-muted-foreground">
                                Jenis Cuti
                            </div>
                            <div>{pengajuan?.jenis_cuti?.nama_jenis}</div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Jumlah Hari
                            </div>
                            <div>{pengajuan?.jumlah_hari} hari</div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Tanggal Mulai
                            </div>
                            <div>
                                {pengajuan?.tanggal_mulai &&
                                    formatDate(pengajuan.tanggal_mulai)}
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Tanggal Selesai
                            </div>
                            <div>
                                {pengajuan?.tanggal_selesai &&
                                    formatDate(pengajuan.tanggal_selesai)}
                            </div>
                        </div>
                        <div className="col-span-2">
                            <div className="text-muted-foreground">
                                Keterangan
                            </div>
                            <div>{pengajuan?.alasan}</div>
                        </div>
                        {pengajuan?.is_mendadak && (
                            <div className="col-span-2">
                                <div className="text-muted-foreground">
                                    Alasan Mendadak
                                </div>
                                <div>{pengajuan.alasan_mendadak}</div>
                            </div>
                        )}
                        {pengajuan?.lampiran && (
                            <div className="col-span-2">
                                <div className="text-muted-foreground">
                                    Lampiran
                                </div>
                                <a
                                    href={`/storage/${pengajuan.lampiran}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 text-primary underline-offset-4 hover:underline"
                                >
                                    <Paperclip className="size-3.5" />
                                    Lihat lampiran
                                </a>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Wallet className="size-4 text-muted-foreground" />
                                Saldo {pengajuan?.jenis_cuti?.nama_jenis}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm">
                            {saldoKaryawan === null ? (
                                <p className="text-muted-foreground">
                                    Karyawan belum punya saldo aktif untuk jenis
                                    cuti ini.
                                </p>
                            ) : saldoKaryawan.kuota === null ? (
                                <p>Tanpa batas kuota.</p>
                            ) : (
                                <div className="grid grid-cols-3 gap-2">
                                    <div>
                                        <div className="text-muted-foreground">
                                            Sisa saat ini
                                        </div>
                                        <div
                                            className={cn(
                                                'font-semibold',
                                                saldoSeverity(
                                                    saldoKaryawan.sisa,
                                                    saldoKaryawan.kuota,
                                                ).text,
                                            )}
                                        >
                                            {saldoKaryawan.sisa} hari
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-muted-foreground">
                                            Terpakai
                                        </div>
                                        <div>
                                            {saldoKaryawan.terpakai}/
                                            {saldoKaryawan.kuota} hari
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-muted-foreground">
                                            Jika disetujui
                                        </div>
                                        <div
                                            className={cn(
                                                'font-semibold',
                                                saldoSeverity(
                                                    sisaSetelahDisetujui,
                                                    saldoKaryawan.kuota,
                                                ).text,
                                            )}
                                        >
                                            {sisaSetelahDisetujui} hari
                                        </div>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="size-4 text-muted-foreground" />
                                Rekan Departemen yang Cuti di Tanggal Sama
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm">
                            {rekanCutiBersamaan.length === 0 ? (
                                <p className="text-muted-foreground">
                                    Tidak ada rekan satu departemen yang cuti
                                    pada rentang tanggal ini.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {rekanCutiBersamaan.map((rekan) => (
                                        <li
                                            key={rekan.id}
                                            className="flex items-center justify-between gap-2 py-2 first:pt-0 last:pb-0"
                                        >
                                            <div>
                                                <div className="font-medium">
                                                    {rekan.karyawan?.nama}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {
                                                        rekan.jenis_cuti
                                                            ?.nama_jenis
                                                    }{' '}
                                                    &middot;{' '}
                                                    {formatDate(
                                                        rekan.tanggal_mulai,
                                                    )}{' '}
                                                    s/d{' '}
                                                    {formatDate(
                                                        rekan.tanggal_selesai,
                                                    )}
                                                </div>
                                            </div>
                                            <StatusBadge
                                                status={rekan.status}
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <History className="size-4 text-muted-foreground" />
                            Riwayat Approval
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="divide-y p-0">
                        <RiwayatApprovalList
                            approvals={pengajuan?.approvals ?? []}
                        />
                    </CardContent>
                </Card>

                {canAct && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CheckSquare className="size-4 text-muted-foreground" />
                                Tindakan
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="catatan">
                                        {pengajuan?.is_mendadak
                                            ? 'Catatan (wajib)'
                                            : 'Catatan (wajib jika menolak)'}
                                    </Label>
                                    <Textarea
                                        id="catatan"
                                        value={data.catatan}
                                        onChange={(e) =>
                                            setData('catatan', e.target.value)
                                        }
                                        maxLength={255}
                                        required={pengajuan?.is_mendadak}
                                    />
                                    <InputError message={errors.catatan} />
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        disabled={processing}
                                        onClick={() => submit('approve')}
                                    >
                                        {aksiBerjalan === 'approve' && (
                                            <Spinner />
                                        )}
                                        Setujui
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="border-destructive/50 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        disabled={processing}
                                        onClick={() => submit('reject')}
                                    >
                                        {aksiBerjalan === 'reject' && (
                                            <Spinner />
                                        )}
                                        Tolak
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

ApprovalShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Approval', href: approvalIndex() },
        { title: 'Detail', href: '#' },
    ],
};
