import { Head, Link, usePage } from '@inertiajs/react';
import { Pagination } from '@/components/pagination';
import { Card, CardContent } from '@/components/ui/card';
import { approvalLevelLabel, formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import {
    index as approvalIndex,
    show as approvalShow,
} from '@/routes/approval';
import type { Approval, Paginated } from '@/types';

type PageProps = {
    approvals: Paginated<Approval>;
};

export default function ApprovalIndex() {
    const { approvals } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Approval Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">
                    Approval Menunggu Tindakan
                </h1>

                <Card>
                    <CardContent className="divide-y p-0">
                        {approvals.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Tidak ada pengajuan yang menunggu persetujuan
                                Anda.
                            </p>
                        )}
                        {approvals.data.map((approval) => (
                            <Link
                                key={approval.id}
                                href={approvalShow(approval.id)}
                                className="flex items-center justify-between p-4 text-sm hover:bg-accent"
                            >
                                <div>
                                    <div className="font-medium">
                                        {
                                            approval.pengajuan_cuti?.karyawan
                                                ?.nama
                                        }{' '}
                                        &middot;{' '}
                                        {
                                            approval.pengajuan_cuti?.jenis_cuti
                                                ?.nama_jenis
                                        }
                                    </div>
                                    <div className="text-muted-foreground">
                                        {approval.pengajuan_cuti
                                            ?.tanggal_mulai &&
                                            formatDate(
                                                approval.pengajuan_cuti
                                                    .tanggal_mulai,
                                            )}{' '}
                                        s/d{' '}
                                        {approval.pengajuan_cuti
                                            ?.tanggal_selesai &&
                                            formatDate(
                                                approval.pengajuan_cuti
                                                    .tanggal_selesai,
                                            )}{' '}
                                        &middot; Level {approval.level} (
                                        {approvalLevelLabel(approval.level)})
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={approvals.links} />
            </div>
        </>
    );
}

ApprovalIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Approval', href: approvalIndex() },
    ],
};
