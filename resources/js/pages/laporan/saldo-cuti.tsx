import { Head, router, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import {
    index as laporanIndex,
    saldoCuti as saldoCutiIndex,
} from '@/routes/laporan';
import type { Departemen, Karyawan, Paginated, SaldoCuti } from '@/types';

type KaryawanRow = Karyawan & { saldo_cutis: SaldoCuti[] };

type PageProps = {
    karyawans: Paginated<KaryawanRow>;
    departemens: Departemen[];
    filters: { search: string; departemen_id: number | null };
};

export default function LaporanSaldoCuti() {
    const { karyawans, departemens, filters } = usePage<PageProps>().props;
    const [form, setForm] = useState({
        search: filters.search ?? '',
        departemen_id: filters.departemen_id
            ? String(filters.departemen_id)
            : '',
    });
    const [expanded, setExpanded] = useState<Set<number>>(
        () => new Set(karyawans.data.map((k) => k.id)),
    );

    const applyFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(saldoCutiIndex.url(), form, { preserveState: true });
    };

    const toggle = (id: number) => {
        setExpanded((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    return (
        <>
            <Head title="Saldo Cuti Karyawan" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Saldo Cuti Karyawan</h1>
                <p className="text-sm text-muted-foreground">
                    Menampilkan sisa saldo cuti seluruh karyawan yang masih
                    berjalan, dikelompokkan per karyawan.
                </p>

                <Card>
                    <CardContent>
                        <form
                            onSubmit={applyFilter}
                            className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto]"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="search">Cari Nama/NIP</Label>
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
                    <CardContent className="divide-y p-0">
                        {karyawans.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Tidak ada karyawan pada filter ini.
                            </p>
                        )}
                        {karyawans.data.map((karyawan) => {
                            const isOpen = expanded.has(karyawan.id);

                            return (
                                <div key={karyawan.id}>
                                    <button
                                        type="button"
                                        onClick={() => toggle(karyawan.id)}
                                        className="flex w-full items-center gap-2 p-4 text-left text-sm hover:bg-muted/50"
                                    >
                                        {isOpen ? (
                                            <ChevronDown className="size-4 shrink-0 text-muted-foreground" />
                                        ) : (
                                            <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                                        )}
                                        <div className="flex-1">
                                            <div className="font-medium">
                                                {karyawan.nama}
                                            </div>
                                            <div className="text-muted-foreground">
                                                {karyawan.nip} &middot;{' '}
                                                {
                                                    karyawan.departemen
                                                        ?.nama_departemen
                                                }{' '}
                                                &middot;{' '}
                                                {karyawan.jabatan?.nama_jabatan}
                                            </div>
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {karyawan.saldo_cutis.length} jenis
                                            cuti
                                        </div>
                                    </button>

                                    {isOpen && (
                                        <div className="bg-muted/20 pl-10">
                                            {karyawan.saldo_cutis.length ===
                                            0 ? (
                                                <p className="p-4 text-sm text-muted-foreground">
                                                    Belum ada saldo cuti aktif.
                                                </p>
                                            ) : (
                                                <div className="divide-y">
                                                    {karyawan.saldo_cutis.map(
                                                        (saldo) => (
                                                            <div
                                                                key={saldo.id}
                                                                className="flex flex-col gap-1 py-3 pr-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                                                            >
                                                                <div>
                                                                    <div className="font-medium">
                                                                        {
                                                                            saldo
                                                                                .jenis_cuti
                                                                                ?.nama_jenis
                                                                        }
                                                                    </div>
                                                                    <div className="text-muted-foreground">
                                                                        {saldo.periode_ke
                                                                            ? `Periode ke-${saldo.periode_ke}`
                                                                            : `Tahun ${saldo.tahun}`}
                                                                    </div>
                                                                </div>
                                                                <div className="text-muted-foreground">
                                                                    Kuota{' '}
                                                                    {saldo.kuota ??
                                                                        'tanpa batas'}
                                                                    , terpakai{' '}
                                                                    {
                                                                        saldo.terpakai
                                                                    }
                                                                    ,{' '}
                                                                    <span className="font-medium text-foreground">
                                                                        sisa{' '}
                                                                        {saldo.sisa ??
                                                                            'tanpa batas'}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        ),
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </CardContent>
                </Card>

                <Pagination links={karyawans.links} />
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
