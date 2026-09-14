import { Head, useForm, usePage } from '@inertiajs/react';
import ApprovalController from '@/actions/App/Http/Controllers/Approval/ApprovalController';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { approvalLevelLabel, formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as approvalIndex } from '@/routes/approval';
import type { Approval } from '@/types';

type PageProps = {
    approval: Approval;
    canAct: boolean;
};

export default function ApprovalShow() {
    const { approval, canAct } = usePage<PageProps>().props;
    const pengajuan = approval.pengajuan_cuti;
    const { data, setData, post, processing, errors } = useForm({
        catatan: '',
    });

    const submit = (aksi: 'approve' | 'reject') => {
        const action =
            aksi === 'approve'
                ? ApprovalController.approve
                : ApprovalController.reject;
        post(action.url(approval.id));
    };

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
                        <CardTitle>Detail Pengajuan</CardTitle>
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
                            <div className="text-muted-foreground">Alasan</div>
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
                        {(pengajuan?.approvals ?? []).map((item) => (
                            <div
                                key={item.id}
                                className="flex flex-col gap-2 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    Level {item.level} (
                                    {approvalLevelLabel(item.level)}) &middot;{' '}
                                    {item.approver?.nama ?? 'Belum ditentukan'}
                                    {item.catatan && (
                                        <div className="text-muted-foreground">
                                            Catatan: {item.catatan}
                                        </div>
                                    )}
                                </div>
                                <StatusBadge status={item.status} />
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {canAct && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Tindakan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="catatan">
                                        {pengajuan?.is_mendadak
                                            ? 'Catatan (wajib untuk pengajuan mendadak)'
                                            : 'Catatan (opsional)'}
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
                                        Setujui
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        disabled={processing}
                                        onClick={() => submit('reject')}
                                    >
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
