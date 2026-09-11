import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Pagination } from '@/components/pagination';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import {
    index as laporanIndex,
    saldoCuti as saldoCutiIndex,
} from '@/routes/laporan';
import type { Departemen, Paginated, SaldoCuti } from '@/types';

type PageProps = {
    saldoCutis: Paginated<SaldoCuti>;
    departemens: Departemen[];
    filters: { search: string; departemen_id: number | null };
};

export default function LaporanSaldoCuti() {
    const { saldoCutis, departemens, filters } = usePage<PageProps>().props;
    const [form, setForm] = useState({
        search: filters.search ?? '',
        departemen_id: filters.departemen_id
            ? String(filters.departemen_id)
            : '',
    });

    const applyFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(saldoCutiIndex.url(), form, { preserveState: true });
    };

    return (
        <>
            <Head title="Saldo Cuti Karyawan" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Saldo Cuti Karyawan</h1>
                <p className="text-sm text-muted-foreground">
                    Menampilkan sisa saldo cuti seluruh karyawan yang masih
                    berjalan.
                </p>

                <Card>
                    <CardContent>
                        <form
                            onSubmit={applyFilter}
                            className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto]"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="search">
                                    Cari Nama/NIP
                                </Label>
                                <Input
                                    id="search"
                                    placeholder="Cari nama/NIP karyawan..."
                                    value={form.search}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            search: e.target.value,
                                        })
                                    }
                                />
                            </div>
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
                            <div className="flex flex-wrap gap-2">
                                <Button type="submit">Filter</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {saldoCutis.data.length === 0 ? (
                            <p className="p-4 text-sm text-muted-foreground">
                                Tidak ada data saldo cuti pada filter ini.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="p-3 font-medium">
                                                Karyawan
                                            </th>
                                            <th className="p-3 font-medium">
                                                Departemen
                                            </th>
                                            <th className="p-3 font-medium">
                                                Jenis Cuti
                                            </th>
                                            <th className="p-3 font-medium">
                                                Periode
                                            </th>
                                            <th className="p-3 text-right font-medium">
                                                Kuota
                                            </th>
                                            <th className="p-3 text-right font-medium">
                                                Terpakai
                                            </th>
                                            <th className="p-3 text-right font-medium">
                                                Sisa
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {saldoCutis.data.map((saldo) => (
                                            <tr key={saldo.id}>
                                                <td className="p-3">
                                                    <div className="font-medium">
                                                        {saldo.karyawan?.nama}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {saldo.karyawan?.nip}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    {
                                                        saldo.karyawan
                                                            ?.departemen
                                                            ?.nama_departemen
                                                    }
                                                </td>
                                                <td className="p-3">
                                                    {
                                                        saldo.jenis_cuti
                                                            ?.nama_jenis
                                                    }
                                                </td>
                                                <td className="p-3 text-muted-foreground">
                                                    {saldo.periode_ke
                                                        ? `Periode ke-${saldo.periode_ke}`
                                                        : `Tahun ${saldo.tahun}`}
                                                </td>
                                                <td className="p-3 text-right">
                                                    {saldo.kuota ??
                                                        'Tanpa batas'}
                                                </td>
                                                <td className="p-3 text-right">
                                                    {saldo.terpakai}
                                                </td>
                                                <td className="p-3 text-right font-medium">
                                                    {saldo.sisa ??
                                                        'Tanpa batas'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Pagination links={saldoCutis.links} />
            </div>
        </>
    );
}

LaporanSaldoCuti.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Laporan', href: laporanIndex() },
        { title: 'Saldo Cuti Karyawan', href: saldoCutiIndex() },
    ],
};
