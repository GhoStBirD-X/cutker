import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
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

type PeriodeOption = {
    periode_ke: number;
    periode_mulai: string;
    periode_selesai: string;
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
    periode_ke: '',
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
    const [periodeOptions, setPeriodeOptions] = useState<PeriodeOption[]>([]);
    const [loadingPeriode, setLoadingPeriode] = useState(false);

    const { data, setData, post, put, processing, errors, reset } =
        useForm(emptyForm);

    const jenisCutiTerpilih = useMemo(
        () => jenisCutis.find((j) => String(j.id) === data.jenis_cuti_id),
        [jenisCutis, data.jenis_cuti_id],
    );
    const bertipePeriode = jenisCutiTerpilih?.masa_kerja_minimal_bulan != null;

    // Periode selalu dihitung dari tanggal_masuk karyawan di backend
    // (lihat SaldoCutiService::periodeTersediaUntuk()) supaya HRD tidak
    // bisa salah ketik tanggal mulai/selesai periode.
    useEffect(() => {
        if (
            editing ||
            !bertipePeriode ||
            !data.karyawan_id ||
            !data.jenis_cuti_id
        ) {
            setPeriodeOptions([]);
            return;
        }

        let dibatalkan = false;
        setLoadingPeriode(true);

        fetch(
            SaldoCutiController.periodeTersedia.url({
                query: {
                    karyawan_id: data.karyawan_id,
                    jenis_cuti_id: data.jenis_cuti_id,
                },
            }),
            { headers: { Accept: 'application/json' } },
        )
            .then((response) => response.json())
            .then((body: { periodes: PeriodeOption[] }) => {
                if (dibatalkan) {
                    return;
                }

                setPeriodeOptions(body.periodes);
                setData(
                    'periode_ke',
                    body.periodes[0] ? String(body.periodes[0].periode_ke) : '',
                );
            })
            .finally(() => {
                if (!dibatalkan) {
                    setLoadingPeriode(false);
                }
            });

        return () => {
            dibatalkan = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.karyawan_id, data.jenis_cuti_id, bertipePeriode, editing]);

    const periodeTerpilih = periodeOptions.find(
        (p) => String(p.periode_ke) === data.periode_ke,
    );

    const startEdit = (saldo: SaldoCutiRow) => {
        setEditing(saldo);
        setData({
            karyawan_id: String(saldo.karyawan_id),
            jenis_cuti_id: String(saldo.jenis_cuti_id),
            tahun: String(saldo.tahun),
            periode_ke: String(saldo.periode_ke ?? ''),
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

    const destroy = (saldo: SaldoCutiRow) => {
        const label = saldo.periode_ke
            ? `periode ke-${saldo.periode_ke}`
            : `tahun ${saldo.tahun}`;

        if (
            confirm(
                `Hapus baris saldo cuti ${saldo.karyawan?.nama} · ${saldo.jenis_cuti?.nama_jenis} (${label})? Tindakan ini tidak bisa dibatalkan.`,
            )
        ) {
            router.delete(SaldoCutiController.destroy.url(saldo.id));
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
                                        <select
                                            id="periode_ke"
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm disabled:opacity-50"
                                            value={data.periode_ke}
                                            disabled={
                                                loadingPeriode ||
                                                periodeOptions.length === 0
                                            }
                                            onChange={(e) =>
                                                setData(
                                                    'periode_ke',
                                                    e.target.value,
                                                )
                                            }
                                        >
                                            {periodeOptions.length === 0 && (
                                                <option value="">
                                                    {loadingPeriode
                                                        ? 'Memuat periode...'
                                                        : !data.karyawan_id
                                                          ? 'Pilih karyawan dahulu'
                                                          : 'Tidak ada periode tersedia'}
                                                </option>
                                            )}
                                            {periodeOptions.map((p) => (
                                                <option
                                                    key={p.periode_ke}
                                                    value={p.periode_ke}
                                                >
                                                    Periode ke-{p.periode_ke}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.periode_ke}
                                        />
                                    </div>
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label>
                                            Rentang Periode (otomatis)
                                        </Label>
                                        <p className="flex h-9 items-center rounded-md border border-dashed border-input px-2 text-sm text-muted-foreground">
                                            {periodeTerpilih
                                                ? `${formatDate(periodeTerpilih.periode_mulai)} s/d ${formatDate(periodeTerpilih.periode_selesai)}`
                                                : 'Dihitung dari tanggal masuk karyawan'}
                                        </p>
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
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        (!editing &&
                                            bertipePeriode &&
                                            !data.periode_ke)
                                    }
                                >
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
                                <div className="flex shrink-0 gap-2 self-start sm:self-center">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(saldo)}
                                    >
                                        Sesuaikan
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => destroy(saldo)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
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
