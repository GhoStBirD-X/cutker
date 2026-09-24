import { Head, router, usePage } from '@inertiajs/react';
import { BarChart3, Download } from 'lucide-react';
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
import type {
    Departemen,
    JenisCuti,
    Karyawan,
    Paginated,
    PengajuanCuti,
} from '@/types';

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
    karyawans: Pick<Karyawan, 'id' | 'nama'>[];
    jenisCutis: Pick<JenisCuti, 'id' | 'nama_jenis'>[];
    filters: {
        departemen_id: number | null;
        karyawan_id: number | null;
        jenis_cuti_id: number | null;
        dari: string | null;
        sampai: string | null;
    };
};

export default function LaporanIndex() {
    const {
        pengajuans,
        lemburSummary,
        departemens,
        karyawans,
        jenisCutis,
        filters,
    } = usePage<PageProps>().props;
    const [form, setForm] = useState({
        departemen_id: filters.departemen_id
            ? String(filters.departemen_id)
            : '',
        karyawan_id: filters.karyawan_id ? String(filters.karyawan_id) : '',
        jenis_cuti_id: filters.jenis_cuti_id
            ? String(filters.jenis_cuti_id)
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
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <BarChart3 className="size-5 text-primary" />
                    Laporan Cuti
                </h1>

                <Card>
                    <CardContent>
                        <form
                            onSubmit={applyFilter}
                            className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="departemen_id">
                                    Departemen
                                </Label>
                                <select
                                    id="departemen_id"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
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
                                <Label htmlFor="karyawan_id">Karyawan</Label>
                                <select
                                    id="karyawan_id"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={form.karyawan_id}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            karyawan_id: e.target.value,
                                        })
                                    }
                                >
                                    <option value="">Semua Karyawan</option>
                                    {karyawans.map((k) => (
                                        <option key={k.id} value={k.id}>
                                            {k.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="jenis_cuti_id">
                                    Jenis Cuti
                                </Label>
                                <select
                                    id="jenis_cuti_id"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={form.jenis_cuti_id}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            jenis_cuti_id: e.target.value,
                                        })
                                    }
                                >
                                    <option value="">Semua Jenis Cuti</option>
                                    {jenisCutis.map((j) => (
                                        <option key={j.id} value={j.id}>
                                            {j.nama_jenis}
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
                            <div className="flex flex-wrap gap-2">
                                <Button type="submit">Filter</Button>
                                <Button asChild variant="outline">
                                    <a
                                        href={exportExcel.url({ query: form })}
                                        className="gap-1.5"
                                    >
                                        <Download className="size-4" />
                                        Excel
                                    </a>
                                </Button>
                                <Button asChild variant="outline">
                                    <a
                                        href={exportPdf.url({ query: form })}
                                        className="gap-1.5"
                                    >
                                        <Download className="size-4" />
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
                                className="flex flex-col gap-2 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
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

                <Pagination
                    links={pengajuans.links}
                    perPage={pengajuans.per_page}
                />

                <h2 className="text-sm font-semibold text-muted-foreground">
                    Ringkasan Lembur
                </h2>

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
                                className="flex flex-col gap-1 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
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
