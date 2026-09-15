import { Head, Link, router, usePage } from '@inertiajs/react';
import { Fragment, useState } from 'react';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { saldoSeverity } from '@/lib/saldo-severity';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    index as laporanIndex,
    saldoCuti as saldoCutiIndex,
} from '@/routes/laporan';
import { riwayat as saldoCutiRiwayat } from '@/routes/laporan/saldo-cuti';
import type {
    Departemen,
    JenisCuti,
    Karyawan,
    Paginated,
    SaldoCuti,
} from '@/types';

type SaldoCutiRow = SaldoCuti & { jenis_cuti?: JenisCuti };

type KaryawanRow = Karyawan & {
    status_kontrak: string;
    saldo_cutis: SaldoCutiRow[];
};

type PageProps = {
    karyawans: Paginated<KaryawanRow>;
    departemens: Departemen[];
    filters: { search: string; departemen_id: number | null };
};

const KOLOM_JENIS_CUTI = [
    'Cuti Tahunan',
    'Cuti Besar',
    'Cuti Haid',
    'Cuti Hamil',
];

function cariSaldo(
    karyawan: KaryawanRow,
    namaJenis: string,
): SaldoCutiRow | undefined {
    return karyawan.saldo_cutis.find(
        (saldo) => saldo.jenis_cuti?.nama_jenis === namaJenis,
    );
}

function relevanUntukKaryawan(karyawan: KaryawanRow, saldo?: SaldoCutiRow) {
    const khusus = saldo?.jenis_cuti?.khusus_gender;

    return !khusus || khusus === karyawan.jenis_kelamin;
}

export default function LaporanSaldoCuti() {
    const { karyawans, departemens, filters } = usePage<PageProps>().props;
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
                    Sisa saldo cuti seluruh karyawan yang masih berjalan. Klik
                    angka &quot;terpakai&quot; untuk melihat riwayat pengajuan
                    yang memotong saldo tersebut.
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
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full min-w-[960px] text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40 text-xs text-muted-foreground">
                                    <th
                                        rowSpan={2}
                                        className="w-10 border-r px-3 py-2 text-left align-bottom"
                                    >
                                        No
                                    </th>
                                    <th
                                        rowSpan={2}
                                        className="border-r px-3 py-2 text-left align-bottom"
                                    >
                                        Nama
                                    </th>
                                    <th
                                        rowSpan={2}
                                        className="border-r px-3 py-2 text-left align-bottom"
                                    >
                                        NIP
                                    </th>
                                    <th
                                        rowSpan={2}
                                        className="border-r px-3 py-2 text-left align-bottom"
                                    >
                                        Status
                                    </th>
                                    {KOLOM_JENIS_CUTI.map((nama) => (
                                        <th
                                            key={nama}
                                            colSpan={2}
                                            className="border-r px-3 py-1.5 text-center font-semibold text-foreground"
                                        >
                                            {nama}
                                        </th>
                                    ))}
                                </tr>
                                <tr className="border-b bg-muted/40 text-xs text-muted-foreground">
                                    {KOLOM_JENIS_CUTI.map((nama) => (
                                        <Fragment key={nama}>
                                            <th className="px-3 py-1.5 text-center font-normal">
                                                Terpakai
                                            </th>
                                            <th className="border-r px-3 py-1.5 text-center font-normal">
                                                Sisa
                                            </th>
                                        </Fragment>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {karyawans.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={
                                                4 + KOLOM_JENIS_CUTI.length * 2
                                            }
                                            className="p-4 text-center text-muted-foreground"
                                        >
                                            Tidak ada karyawan pada filter ini.
                                        </td>
                                    </tr>
                                )}
                                {karyawans.data.map((karyawan, index) => (
                                    <tr
                                        key={karyawan.id}
                                        className="transition-colors hover:bg-muted/30"
                                    >
                                        <td className="border-r px-3 py-2 text-muted-foreground">
                                            {(karyawans.from ?? 1) + index}
                                        </td>
                                        <td className="border-r px-3 py-2 font-medium">
                                            {karyawan.nama}
                                        </td>
                                        <td className="border-r px-3 py-2 text-muted-foreground">
                                            {karyawan.nip}
                                        </td>
                                        <td className="border-r px-3 py-2">
                                            <Badge variant="outline">
                                                {karyawan.status_kontrak}
                                            </Badge>
                                        </td>
                                        {KOLOM_JENIS_CUTI.map((nama) => {
                                            const saldo = cariSaldo(
                                                karyawan,
                                                nama,
                                            );
                                            const relevan =
                                                relevanUntukKaryawan(
                                                    karyawan,
                                                    saldo,
                                                );

                                            if (!saldo || !relevan) {
                                                return (
                                                    <Fragment key={nama}>
                                                        <td className="px-3 py-2 text-center text-muted-foreground">
                                                            &mdash;
                                                        </td>
                                                        <td className="border-r px-3 py-2 text-center text-muted-foreground">
                                                            &mdash;
                                                        </td>
                                                    </Fragment>
                                                );
                                            }

                                            const style = saldoSeverity(
                                                saldo.sisa,
                                                saldo.kuota,
                                            );

                                            return (
                                                <Fragment key={nama}>
                                                    <td className="px-3 py-2 text-center">
                                                        <Link
                                                            href={saldoCutiRiwayat(
                                                                saldo.id,
                                                            )}
                                                            className="text-primary underline-offset-4 hover:underline"
                                                        >
                                                            {saldo.terpakai}
                                                        </Link>
                                                    </td>
                                                    <td
                                                        className={cn(
                                                            'border-r px-3 py-2 text-center font-semibold',
                                                            style.text,
                                                        )}
                                                    >
                                                        {saldo.sisa ?? '∞'}
                                                    </td>
                                                </Fragment>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
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
