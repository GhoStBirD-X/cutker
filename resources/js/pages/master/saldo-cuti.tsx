import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import SaldoCutiController from '@/actions/App/Http/Controllers/Master/SaldoCutiController';
import InputError from '@/components/input-error';
import { MasterNav } from '@/components/master-nav';
import { Pagination } from '@/components/pagination';
import { SaldoCutiInline } from '@/components/saldo-cuti-meter';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as saldoCutiIndex } from '@/routes/master/saldo-cuti';
import type { JenisCuti, Karyawan, Paginated, SaldoCuti } from '@/types';

type SaldoCutiRow = SaldoCuti & {
    karyawan?: Pick<Karyawan, 'id' | 'nama' | 'nip'>;
    jenis_cuti?: JenisCuti;
    diubah_oleh?: Pick<Karyawan, 'id' | 'nama'>;
};

type PageProps = {
    saldoCutis: Paginated<SaldoCutiRow>;
    karyawans: Pick<Karyawan, 'id' | 'nama' | 'nip'>[];
    jenisCutis: JenisCuti[];
    filters: { search: string };
};

const emptyForm = {
    karyawan_id: '',
    jenis_cuti_id: '',
    tahun: String(new Date().getFullYear()),
    periode_ke: '1',
    periode_mulai: '',
    periode_selesai: '',
    kuota: '',
    terpakai: '0',
    sisa: '',
    catatan: '',
};

export default function MasterSaldoCuti() {
    const { saldoCutis, karyawans, jenisCutis, filters } =
        usePage<PageProps>().props;
    const [editing, setEditing] = useState<SaldoCutiRow | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const { data, setData, post, put, processing, errors, reset } =
        useForm(emptyForm);

    const jenisCutiTerpilih = useMemo(
        () => jenisCutis.find((j) => String(j.id) === data.jenis_cuti_id),
        [jenisCutis, data.jenis_cuti_id],
    );
    const bertipePeriode = jenisCutiTerpilih?.masa_kerja_minimal_bulan != null;

    const startEdit = (saldo: SaldoCutiRow) => {
        setEditing(saldo);
        setData({
            karyawan_id: String(saldo.karyawan_id),
            jenis_cuti_id: String(saldo.jenis_cuti_id),
            tahun: String(saldo.tahun),
            periode_ke: String(saldo.periode_ke ?? 1),
            periode_mulai: saldo.periode_mulai?.slice(0, 10) ?? '',
            periode_selesai: saldo.periode_selesai?.slice(0, 10) ?? '',
            kuota: saldo.kuota === null ? '' : String(saldo.kuota),
            terpakai: String(saldo.terpakai),
            sisa: saldo.sisa === null ? '' : String(saldo.sisa),
            catatan: '',
        });
    };

    const cancelEdit = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            put(SaldoCutiController.update.url(editing.id), {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post(SaldoCutiController.store.url(), {
                onSuccess: () => reset(),
            });
        }
    };

    const runSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(saldoCutiIndex.url(), { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Master Saldo Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Master Saldo Cuti</h1>
                <p className="text-sm text-muted-foreground">
                    Dipakai untuk memasukkan saldo cuti karyawan lama secara
                    manual saat aplikasi ini mulai dipakai di tengah tahun
                    berjalan, atau mengoreksi kuota/terpakai/sisa yang sudah
                    ada. Setiap koreksi wajib disertai catatan sebagai jejak
                    audit.
                </p>

                <MasterNav />

                <Card className="max-w-4xl">
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="karyawan_id">Karyawan</Label>
                                <select
                                    id="karyawan_id"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={data.karyawan_id}
                                    disabled={!!editing}
                                    onChange={(e) =>
                                        setData('karyawan_id', e.target.value)
                                    }
                                >
                                    <option value="">Pilih karyawan</option>
                                    {karyawans.map((k) => (
                                        <option key={k.id} value={k.id}>
                                            {k.nama} ({k.nip})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.karyawan_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="jenis_cuti_id">
                                    Jenis Cuti
                                </Label>
                                <select
                                    id="jenis_cuti_id"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={data.jenis_cuti_id}
                                    disabled={!!editing}
                                    onChange={(e) =>
                                        setData('jenis_cuti_id', e.target.value)
                                    }
                                >
                                    <option value="">Pilih jenis cuti</option>
                                    {jenisCutis.map((j) => (
                                        <option key={j.id} value={j.id}>
                                            {j.nama_jenis}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.jenis_cuti_id} />
                            </div>

                            {!editing && bertipePeriode && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="periode_ke">
                                            Periode Ke-
                                        </Label>
                                        <Input
                                            id="periode_ke"
                                            type="number"
                                            min={1}
                                            value={data.periode_ke}
                                            onChange={(e) =>
                                                setData(
                                                    'periode_ke',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.periode_ke}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="periode_mulai">
                                            Periode Mulai
                                        </Label>
                                        <Input
                                            id="periode_mulai"
                                            type="date"
                                            value={data.periode_mulai}
                                            onChange={(e) =>
                                                setData(
                                                    'periode_mulai',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.periode_mulai}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="periode_selesai">
                                            Periode Selesai
                                        </Label>
                                        <Input
                                            id="periode_selesai"
                                            type="date"
                                            value={data.periode_selesai}
                                            onChange={(e) =>
                                                setData(
                                                    'periode_selesai',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.periode_selesai}
                                        />
                                    </div>
                                </>
                            )}
                            {!editing && !bertipePeriode && (
                                <div className="grid gap-2">
                                    <Label htmlFor="tahun">Tahun</Label>
                                    <Input
                                        id="tahun"
                                        type="number"
                                        value={data.tahun}
                                        onChange={(e) =>
                                            setData('tahun', e.target.value)
                                        }
                                    />
                                    <InputError message={errors.tahun} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="kuota">
                                    Kuota (kosong = tanpa batas)
                                </Label>
                                <Input
                                    id="kuota"
                                    type="number"
                                    min={0}
                                    value={data.kuota}
                                    onChange={(e) =>
                                        setData('kuota', e.target.value)
                                    }
                                />
                                <InputError message={errors.kuota} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="terpakai">Terpakai</Label>
                                <Input
                                    id="terpakai"
                                    type="number"
                                    min={0}
                                    value={data.terpakai}
                                    onChange={(e) =>
                                        setData('terpakai', e.target.value)
                                    }
                                />
                                <InputError message={errors.terpakai} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="sisa">
                                    Sisa (kosong = tanpa batas)
                                </Label>
                                <Input
                                    id="sisa"
                                    type="number"
                                    min={0}
                                    value={data.sisa}
                                    onChange={(e) =>
                                        setData('sisa', e.target.value)
                                    }
                                />
                                <InputError message={errors.sisa} />
                            </div>
                            <div className="col-span-1 grid gap-2 sm:col-span-2 md:col-span-4">
                                <Label htmlFor="catatan">
                                    Catatan{' '}
                                    {editing
                                        ? '(wajib diisi untuk koreksi)'
                                        : '(opsional)'}
                                </Label>
                                <Input
                                    id="catatan"
                                    value={data.catatan}
                                    onChange={(e) =>
                                        setData('catatan', e.target.value)
                                    }
                                />
                                <InputError message={errors.catatan} />
                            </div>
                            <div className="col-span-1 flex flex-wrap items-end gap-2 sm:col-span-2 md:col-span-4">
                                <Button type="submit" disabled={processing}>
                                    {editing ? 'Simpan Koreksi' : 'Tambah'}
                                </Button>
                                {editing && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={cancelEdit}
                                    >
                                        Batal
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <form onSubmit={runSearch} className="max-w-sm">
                    <Input
                        placeholder="Cari nama/NIP karyawan..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </form>

                <Card>
                    <CardContent className="divide-y p-0">
                        {saldoCutis.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Belum ada data saldo cuti aktif.
                            </p>
                        )}
                        {saldoCutis.data.map((saldo) => (
                            <div
                                key={saldo.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        {saldo.karyawan?.nama} &middot;{' '}
                                        {saldo.jenis_cuti?.nama_jenis}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {saldo.periode_ke
                                            ? `Periode ke-${saldo.periode_ke} (${formatDate(saldo.periode_mulai!)} s/d ${formatDate(saldo.periode_selesai!)})`
                                            : `Tahun ${saldo.tahun}`}
                                    </div>
                                    <SaldoCutiInline
                                        nama=""
                                        sisa={saldo.sisa}
                                        kuota={saldo.kuota}
                                        terpakai={saldo.terpakai}
                                        className="mt-1"
                                    />
                                    {saldo.catatan && (
                                        <div className="mt-1 flex flex-wrap items-center gap-1 text-xs text-muted-foreground">
                                            <Badge variant="outline">
                                                Disesuaikan manual
                                            </Badge>
                                            {saldo.catatan} &middot;{' '}
                                            {saldo.diubah_oleh?.nama}
                                        </div>
                                    )}
                                </div>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="self-start sm:self-center"
                                    onClick={() => startEdit(saldo)}
                                >
                                    Sesuaikan
                                </Button>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination
                    links={saldoCutis.links}
                    perPage={saldoCutis.per_page}
                />
            </div>
        </>
    );
}

MasterSaldoCuti.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Master Saldo Cuti', href: saldoCutiIndex() },
    ],
};
