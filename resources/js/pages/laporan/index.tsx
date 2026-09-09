import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as laporanIndex } from '@/routes/laporan';
import {
    excel as exportExcel,
    pdf as exportPdf,
} from '@/routes/laporan/export';
import type { Departemen, Paginated, PengajuanCuti } from '@/types';

type LemburSummary = {
    karyawan_id: number;
    nama: string;
    departemen: string;
    total_jam_lembur: number;
};

type PageProps = {
    pengajuans: Paginated<PengajuanCuti>;
    lemburSummary: LemburSummary[];
    departemens: Departemen[];
    filters: {
        departemen_id: number | null;
        dari: string | null;
        sampai: string | null;
    };
};

export default function LaporanIndex() {
    const { pengajuans, lemburSummary, departemens, filters } =
        usePage<PageProps>().props;
    const [form, setForm] = useState({
        departemen_id: filters.departemen_id
            ? String(filters.departemen_id)
            : '',
        dari: filters.dari ?? '',
        sampai: filters.sampai ?? '',
    });

    const applyFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(laporanIndex.url(), form, { preserveState: true });
    };

    return (
        <>
            <Head title="Laporan Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Laporan Cuti</h1>

                <Card>
                    <CardContent>
                        <form
                            onSubmit={applyFilter}
                            className="grid grid-cols-[1fr_1fr_1fr_auto_auto] items-end gap-3"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="departemen_id">
                                    Departemen
                                </Label>
                                <select
                                    id="departemen_id"
                                    className="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={form.departemen_id}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            departemen_id: e.target.value,
                                        })
                                    }
                                >
                                    <option value="">Semua Departemen</option>
                                    {departemens.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.nama_departemen}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="dari">Dari Tanggal</Label>
                                <Input
                                    id="dari"
                                    type="date"
                                    value={form.dari}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            dari: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="sampai">Sampai Tanggal</Label>
                                <Input
                                    id="sampai"
                                    type="date"
                                    value={form.sampai}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            sampai: e.target.value,
                                        })
                                    }
                                />
                            </div>
                            <Button type="submit">Filter</Button>
                            <div className="flex gap-2">
                                <Button asChild variant="outline">
                                    <a href={exportExcel.url({ query: form })}>
                                        Excel
                                    </a>
                                </Button>
                                <Button asChild variant="outline">
                                    <a href={exportPdf.url({ query: form })}>
                                        PDF
                                    </a>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="divide-y p-0">
                        {pengajuans.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Tidak ada data pada filter ini.
                            </p>
                        )}
                        {pengajuans.data.map((pengajuan) => (
                            <div
                                key={pengajuan.id}
                                className="flex items-center justify-between p-4 text-sm"
                            >
                                <div>
                                    <div className="font-medium">
                                        {pengajuan.karyawan?.nama} &middot;{' '}
                                        {pengajuan.jenis_cuti?.nama_jenis}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {
                                            pengajuan.karyawan?.departemen
                                                ?.nama_departemen
                                        }{' '}
                                        &middot;{' '}
                                        {formatDate(pengajuan.tanggal_mulai)}{' '}
                                        s/d{' '}
                                        {formatDate(pengajuan.tanggal_selesai)}{' '}
                                        ({pengajuan.jumlah_hari} hari)
                                    </div>
                                </div>
                                <StatusBadge status={pengajuan.status} />
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={pengajuans.links} />

                <h2 className="text-lg font-semibold">Ringkasan Lembur</h2>

                <Card>
                    <CardContent className="divide-y p-0">
                        {lemburSummary.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Tidak ada data lembur pada filter ini.
                            </p>
                        )}
                        {lemburSummary.map((ringkasan) => (
                            <div
                                key={ringkasan.karyawan_id}
                                className="flex items-center justify-between p-4 text-sm"
                            >
                                <div>
                                    <div className="font-medium">
                                        {ringkasan.nama}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {ringkasan.departemen}
                                    </div>
                                </div>
                                <div className="font-medium">
                                    {ringkasan.total_jam_lembur} jam
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

LaporanIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Laporan', href: laporanIndex() },
    ],
};
