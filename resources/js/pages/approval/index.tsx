import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, ClipboardCheck, Zap } from 'lucide-react';
import { useState } from 'react';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { approvalLevelLabel, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    index as approvalIndex,
    show as approvalShow,
} from '@/routes/approval';
import type { Approval, Paginated } from '@/types';

type PageProps = {
    approvals: Paginated<Approval>;
    filters: { search: string; mendadak: boolean };
};

export default function ApprovalIndex() {
    const { approvals, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search);
    const adaFilter = filters.search !== '' || filters.mendadak;

    const terapkanFilter = (perubahan: Partial<PageProps['filters']> = {}) => {
        const berikut = { ...filters, search, ...perubahan };

        router.get(
            approvalIndex.url(),
            {
                search: berikut.search || undefined,
                mendadak: berikut.mendadak ? 1 : undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Approval Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="flex items-center gap-2 text-xl font-semibold">
                        <ClipboardCheck className="size-5 text-amber-600 dark:text-amber-400" />
                        Approval Menunggu Tindakan
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Pengajuan mendadak ditandai khusus karena butuh
                        keputusan lebih cepat.
                    </p>
                </div>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        terapkanFilter();
                    }}
                    className="flex flex-wrap items-center gap-3"
                >
                    <Input
                        placeholder="Cari nama karyawan..."
                        aria-label="Cari nama karyawan"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="max-w-sm"
                    />
                    <Button type="submit" variant="outline">
                        Cari
                    </Button>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="hanya-mendadak"
                            checked={filters.mendadak}
                            onCheckedChange={(checked) =>
                                terapkanFilter({ mendadak: checked === true })
                            }
                        />
                        <Label htmlFor="hanya-mendadak">
                            Hanya pengajuan mendadak
                        </Label>
                    </div>
                    {adaFilter && (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => {
                                setSearch('');
                                terapkanFilter({
                                    search: '',
                                    mendadak: false,
                                });
                            }}
                        >
                            Hapus filter
                        </Button>
                    )}
                </form>

                <Card className="gap-0 overflow-hidden py-0">
                    <CardContent className="divide-y p-0">
                        {approvals.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                {adaFilter
                                    ? 'Tidak ada pengajuan yang cocok dengan filter.'
                                    : 'Tidak ada pengajuan yang menunggu persetujuan Anda.'}
                            </p>
                        )}
                        {approvals.data.map((approval) => {
                            const mendadak =
                                approval.pengajuan_cuti?.is_mendadak;

                            return (
                                <Link
                                    key={approval.id}
                                    href={approvalShow(approval.id)}
                                    className={cn(
                                        'flex items-center justify-between gap-3 p-4 text-sm transition-colors hover:bg-accent',
                                        mendadak &&
                                            'bg-amber-50/50 dark:bg-amber-950/10',
                                    )}
                                >
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2 font-medium">
                                            {
                                                approval.pengajuan_cuti
                                                    ?.karyawan?.nama
                                            }{' '}
                                            &middot;{' '}
                                            {
                                                approval.pengajuan_cuti
                                                    ?.jenis_cuti?.nama_jenis
                                            }
                                            {mendadak && (
                                                <Badge className="gap-1 border-amber-200 bg-amber-100 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300">
                                                    <Zap className="size-3" />
                                                    Mendadak
                                                </Badge>
                                            )}
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
                                            {approvalLevelLabel(approval.level)}
                                            )
                                        </div>
                                    </div>
                                    <ArrowRight className="size-4 shrink-0 text-muted-foreground" />
                                </Link>
                            );
                        })}
                    </CardContent>
                </Card>

                <Pagination
                    links={approvals.links}
                    perPage={approvals.per_page}
                />
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
