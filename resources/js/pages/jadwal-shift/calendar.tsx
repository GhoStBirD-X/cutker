import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import JadwalShiftController from '@/actions/App/Http/Controllers/JadwalShift/JadwalShiftController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as jadwalShiftIndex } from '@/routes/jadwal-shift';
import type { Auth, Departemen, JadwalShift, Karyawan, Shift } from '@/types';

type PageProps = {
    auth: Auth;
    jadwals: JadwalShift[];
    shifts: Shift[];
    karyawans: Pick<Karyawan, 'id' | 'nama' | 'departemen_id'>[];
    departemens: Departemen[];
    filters: { bulan: number; tahun: number; departemen_id: number | null };
    departemenTerkunci: boolean;
};

const NAMA_BULAN = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const weekdayFormatter = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
});

const SHIFT_BADGE_CLASS: Record<string, string> = {
    Pagi: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-900',
    Siang: 'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-900',
    Malam: 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-900',
};

export default function JadwalShiftCalendar() {
    const {
        auth,
        jadwals,
        shifts,
        karyawans,
        departemens,
        filters,
        departemenTerkunci,
    } = usePage<PageProps>().props;
    const bisaKelola =
        auth.roles.includes('hrd') ||
        auth.roles.includes('admin') ||
        auth.roles.includes('koordinator_shift');
    const bisaPilihDepartemen =
        (auth.roles.includes('hrd') || auth.roles.includes('admin')) &&
        !departemenTerkunci;

    const departemenSaatIni = departemens.find(
        (d) => d.id === filters.departemen_id,
    );

    const [cariKaryawan, setCariKaryawan] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm({
        karyawan_ids: [] as number[],
        shift_id: '',
        tanggal_mulai: '',
        tanggal_selesai: '',
    });

    const karyawanTersaring = karyawans.filter((k) =>
        k.nama.toLowerCase().includes(cariKaryawan.toLowerCase()),
    );

    const semuaTerlihatTerpilih =
        karyawanTersaring.length > 0 &&
        karyawanTersaring.every((k) => data.karyawan_ids.includes(k.id));

    const toggleKaryawan = (id: number) => {
        setData(
            'karyawan_ids',
            data.karyawan_ids.includes(id)
                ? data.karyawan_ids.filter((x) => x !== id)
                : [...data.karyawan_ids, id],
        );
    };

    const toggleSemuaTerlihat = () => {
        const idTerlihat = karyawanTersaring.map((k) => k.id);
        setData(
            'karyawan_ids',
            semuaTerlihatTerpilih
                ? data.karyawan_ids.filter((id) => !idTerlihat.includes(id))
                : [...new Set([...data.karyawan_ids, ...idTerlihat])],
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(JadwalShiftController.store.url(), {
            onSuccess: () => reset('karyawan_ids'),
        });
    };

    const [editingLemburId, setEditingLemburId] = useState<number | null>(null);
    const [lemburJam, setLemburJam] = useState('');
    const [lemburCatatan, setLemburCatatan] = useState('');
    const [lemburErrors, setLemburErrors] = useState<{
        jam_lembur?: string;
        catatan_lembur?: string;
    }>({});
    const [lemburProcessing, setLemburProcessing] = useState(false);

    const startEditLembur = (jadwal: JadwalShift) => {
        setEditingLemburId(jadwal.id);
        setLemburJam(jadwal.jam_lembur ?? '');
        setLemburCatatan(jadwal.catatan_lembur ?? '');
        setLemburErrors({});
    };

    const cancelEditLembur = () => {
        setEditingLemburId(null);
        setLemburErrors({});
    };

    const submitLembur = (jadwal: JadwalShift) => {
        setLemburProcessing(true);
        router.patch(
            JadwalShiftController.updateLembur.url(jadwal.id),
            {
                jam_lembur: lemburJam === '' ? null : lemburJam,
                catatan_lembur: lemburCatatan === '' ? null : lemburCatatan,
            },
            {
                preserveScroll: true,
                onSuccess: () => setEditingLemburId(null),
                onError: (errs) =>
                    setLemburErrors(
                        errs as {
                            jam_lembur?: string;
                            catatan_lembur?: string;
                        },
                    ),
                onFinish: () => setLemburProcessing(false),
            },
        );
    };

    const destroy = (jadwal: JadwalShift) => {
        if (
            confirm(
                `Hapus jadwal shift ${jadwal.karyawan?.nama} pada ${formatDate(jadwal.tanggal)}?`,
            )
        ) {
            router.delete(JadwalShiftController.destroy.url(jadwal.id));
        }
    };

    const gantiPeriode = (bulan: number, tahun: number) => {
        router.get(
            jadwalShiftIndex.url(),
            { bulan, tahun, departemen_id: filters.departemen_id },
            { preserveState: true },
        );
    };

    const gantiDepartemen = (departemenId: string) => {
        router.get(
            jadwalShiftIndex.url(),
            {
                bulan: filters.bulan,
                tahun: filters.tahun,
                departemen_id: departemenId || undefined,
            },
            { preserveState: true },
        );
    };

    const bulanSebelumnya =
        filters.bulan === 1
            ? { bulan: 12, tahun: filters.tahun - 1 }
            : { bulan: filters.bulan - 1, tahun: filters.tahun };
    const bulanBerikutnya =
        filters.bulan === 12
            ? { bulan: 1, tahun: filters.tahun + 1 }
            : { bulan: filters.bulan + 1, tahun: filters.tahun };

    const jadwalPerTanggal = useMemo(() => {
        const groups = new Map<string, JadwalShift[]>();

        for (const jadwal of jadwals) {
            const list = groups.get(jadwal.tanggal) ?? [];
            list.push(jadwal);
            groups.set(jadwal.tanggal, list);
        }

        return Array.from(groups.entries())
            .sort(([a], [b]) => a.localeCompare(b))
            .map(
                ([tanggal, items]) =>
                    [
                        tanggal,
                        items.sort((a, b) =>
                            (a.karyawan?.nama ?? '').localeCompare(
                                b.karyawan?.nama ?? '',
                            ),
                        ),
                    ] as const,
            );
    }, [jadwals]);

    return (
        <>
            <Head title="Jadwal Shift" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Jadwal Shift</h1>
                        {departemenTerkunci && departemenSaatIni && (
                            <p className="text-sm text-muted-foreground">
                                Departemen {departemenSaatIni.nama_departemen}
                            </p>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                gantiPeriode(
                                    bulanSebelumnya.bulan,
                                    bulanSebelumnya.tahun,
                                )
                            }
                        >
                            &larr;
                        </Button>
                        <span className="min-w-32 text-center text-sm font-medium">
                            {NAMA_BULAN[filters.bulan - 1]} {filters.tahun}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                gantiPeriode(
                                    bulanBerikutnya.bulan,
                                    bulanBerikutnya.tahun,
                                )
                            }
                        >
                            &rarr;
                        </Button>

                        {bisaPilihDepartemen && (
                            <select
                                className="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                                value={filters.departemen_id ?? ''}
                                onChange={(e) =>
                                    gantiDepartemen(e.target.value)
                                }
                            >
                                <option value="">Semua Departemen</option>
                                {departemens.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.nama_departemen}
                                    </option>
                                ))}
                            </select>
                        )}
                    </div>
                </div>

                {bisaKelola && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">
                                Tambah Jadwal Shift
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="shift_id">Shift</Label>
                                        <select
                                            id="shift_id"
                                            className="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                                            value={data.shift_id}
                                            onChange={(e) =>
                                                setData(
                                                    'shift_id',
                                                    e.target.value,
                                                )
                                            }
                                        >
                                            <option value="">
                                                Pilih shift
                                            </option>
                                            {shifts.map((s) => (
                                                <option key={s.id} value={s.id}>
                                                    {s.nama_shift} (
                                                    {s.jam_mulai}-
                                                    {s.jam_selesai})
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.shift_id} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="tanggal_mulai">
                                            Dari Tanggal
                                        </Label>
                                        <Input
                                            id="tanggal_mulai"
                                            type="date"
                                            value={data.tanggal_mulai}
                                            onChange={(e) =>
                                                setData(
                                                    'tanggal_mulai',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.tanggal_mulai}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="tanggal_selesai">
                                            Sampai Tanggal
                                        </Label>
                                        <Input
                                            id="tanggal_selesai"
                                            type="date"
                                            value={data.tanggal_selesai}
                                            onChange={(e) =>
                                                setData(
                                                    'tanggal_selesai',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.tanggal_selesai}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <div className="flex items-center justify-between">
                                        <Label>Pilih Karyawan</Label>
                                        <span className="text-xs text-muted-foreground">
                                            {data.karyawan_ids.length} dipilih
                                        </span>
                                    </div>
                                    <Input
                                        placeholder="Cari nama karyawan..."
                                        value={cariKaryawan}
                                        onChange={(e) =>
                                            setCariKaryawan(e.target.value)
                                        }
                                    />
                                    <label className="flex items-center gap-2 border-b pb-2 text-sm text-muted-foreground">
                                        <Checkbox
                                            checked={semuaTerlihatTerpilih}
                                            onCheckedChange={
                                                toggleSemuaTerlihat
                                            }
                                        />
                                        Pilih semua yang terlihat (
                                        {karyawanTersaring.length})
                                    </label>
                                    <div className="grid max-h-56 gap-1 overflow-y-auto rounded-md border p-2 sm:grid-cols-2 lg:grid-cols-3">
                                        {karyawanTersaring.map((k) => (
                                            <label
                                                key={k.id}
                                                className="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-accent"
                                            >
                                                <Checkbox
                                                    checked={data.karyawan_ids.includes(
                                                        k.id,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleKaryawan(k.id)
                                                    }
                                                />
                                                {k.nama}
                                            </label>
                                        ))}
                                        {karyawanTersaring.length === 0 && (
                                            <p className="col-span-full p-2 text-sm text-muted-foreground">
                                                Tidak ada karyawan yang cocok.
                                            </p>
                                        )}
                                    </div>
                                    <InputError message={errors.karyawan_ids} />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        data.karyawan_ids.length === 0
                                    }
                                >
                                    Tambah untuk {data.karyawan_ids.length}{' '}
                                    Karyawan
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {jadwalPerTanggal.length === 0 && (
                    <Card>
                        <CardContent className="p-4 text-sm text-muted-foreground">
                            Tidak ada jadwal shift pada periode ini.
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-3">
                    {jadwalPerTanggal.map(([tanggal, items]) => (
                        <Card key={tanggal}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium capitalize">
                                    {weekdayFormatter.format(new Date(tanggal))}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="divide-y p-0">
                                {items.map((jadwal) => (
                                    <div
                                        key={jadwal.id}
                                        className="px-4 py-2.5"
                                    >
                                        <div className="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-medium">
                                                    {jadwal.karyawan?.nama}
                                                </span>
                                                <Badge
                                                    className={cn(
                                                        jadwal.shift &&
                                                            SHIFT_BADGE_CLASS[
                                                                jadwal.shift
                                                                    .nama_shift
                                                            ],
                                                    )}
                                                    variant="outline"
                                                >
                                                    {jadwal.shift?.nama_shift}{' '}
                                                    &middot;{' '}
                                                    {jadwal.shift?.jam_mulai}-
                                                    {jadwal.shift?.jam_selesai}
                                                </Badge>
                                                {jadwal.jam_lembur && (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-orange-200 bg-orange-100 text-orange-800 dark:border-orange-900 dark:bg-orange-950 dark:text-orange-300"
                                                    >
                                                        {jadwal.jam_lembur} jam
                                                        lembur
                                                        {jadwal.catatan_lembur
                                                            ? ` · ${jadwal.catatan_lembur}`
                                                            : ''}
                                                    </Badge>
                                                )}
                                            </div>
                                            {bisaKelola && (
                                                <div className="flex gap-1">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            editingLemburId ===
                                                            jadwal.id
                                                                ? cancelEditLembur()
                                                                : startEditLembur(
                                                                      jadwal,
                                                                  )
                                                        }
                                                    >
                                                        Lembur
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        className="text-destructive"
                                                        onClick={() =>
                                                            destroy(jadwal)
                                                        }
                                                    >
                                                        Hapus
                                                    </Button>
                                                </div>
                                            )}
                                        </div>

                                        {editingLemburId === jadwal.id && (
                                            <div className="mt-2 grid grid-cols-1 gap-2 rounded-md border bg-muted/30 p-3 sm:grid-cols-[100px_1fr_auto_auto]">
                                                <div className="grid gap-1">
                                                    <Label
                                                        htmlFor={`jam-lembur-${jadwal.id}`}
                                                        className="text-xs"
                                                    >
                                                        Jam Lembur
                                                    </Label>
                                                    <Input
                                                        id={`jam-lembur-${jadwal.id}`}
                                                        type="number"
                                                        step="0.5"
                                                        min={0}
                                                        max={12}
                                                        value={lemburJam}
                                                        onChange={(e) =>
                                                            setLemburJam(
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            lemburErrors.jam_lembur
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1">
                                                    <Label
                                                        htmlFor={`catatan-lembur-${jadwal.id}`}
                                                        className="text-xs"
                                                    >
                                                        Catatan (opsional)
                                                    </Label>
                                                    <Input
                                                        id={`catatan-lembur-${jadwal.id}`}
                                                        value={lemburCatatan}
                                                        onChange={(e) =>
                                                            setLemburCatatan(
                                                                e.target.value,
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            lemburErrors.catatan_lembur
                                                        }
                                                    />
                                                </div>
                                                <Button
                                                    size="sm"
                                                    className="self-end"
                                                    disabled={lemburProcessing}
                                                    onClick={() =>
                                                        submitLembur(jadwal)
                                                    }
                                                >
                                                    Simpan
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="self-end"
                                                    onClick={cancelEditLembur}
                                                >
                                                    Batal
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

JadwalShiftCalendar.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Jadwal Shift', href: jadwalShiftIndex() },
    ],
};
