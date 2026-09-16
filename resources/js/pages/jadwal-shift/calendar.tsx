import { Head, router, useForm, usePage } from '@inertiajs/react';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import JadwalShiftController from '@/actions/App/Http/Controllers/JadwalShift/JadwalShiftController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

const weekdayShortFormatter = new Intl.DateTimeFormat('id-ID', {
    weekday: 'short',
});

const SHIFT_BADGE_CLASS: Record<string, string> = {
    Pagi: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-900',
    Siang: 'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-900',
    Malam: 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-900',
};

/** Parse "YYYY-MM-DD" sebagai tanggal lokal, menghindari geser sehari akibat parsing UTC bawaan `new Date(string)`. */
function parseTanggalLocal(tanggal: string): Date {
    const [tahun, bulan, hari] = tanggal.split('-').map(Number);

    return new Date(tahun, bulan - 1, hari);
}

function tanggalHariIni(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

function semuaTanggalDiBulan(bulan: number, tahun: number): string[] {
    const jumlahHari = new Date(tahun, bulan, 0).getDate();
    const bulanStr = String(bulan).padStart(2, '0');

    return Array.from(
        { length: jumlahHari },
        (_, i) => `${tahun}-${bulanStr}-${String(i + 1).padStart(2, '0')}`,
    );
}

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
    const bisaLihatSemuaDepartemen =
        auth.roles.includes('hrd') ||
        auth.roles.includes('admin') ||
        auth.roles.includes('kepala_bagian') ||
        auth.roles.includes('manager') ||
        auth.roles.includes('koordinator_shift');
    const bisaPilihDepartemen = bisaLihatSemuaDepartemen && !departemenTerkunci;

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
        const tanggalMulaiDiinput = data.tanggal_mulai;
        post(JadwalShiftController.store.url(), {
            onSuccess: () => {
                reset('karyawan_ids');

                // Pindahkan tanggal yang dipilih di strip kalender ke
                // tanggal yang baru diinput -- kalau tidak, halaman sudah
                // berada di bulan yang benar tapi strip tetap menampilkan
                // hari lain (mis. hari ini) sehingga daftar karyawan yang
                // baru dijadwalkan terlihat kosong.
                if (tanggalMulaiDiinput) {
                    setSelectedDate(tanggalMulaiDiinput);
                }
            },
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
                `Hapus jadwal shift ${jadwal.karyawan?.nama} pada ${weekdayFormatter.format(parseTanggalLocal(jadwal.tanggal))}?`,
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
        const map = new Map<string, JadwalShift[]>();

        for (const jadwal of jadwals) {
            const list = map.get(jadwal.tanggal) ?? [];
            list.push(jadwal);
            map.set(jadwal.tanggal, list);
        }

        for (const items of map.values()) {
            items.sort((a, b) =>
                (a.karyawan?.nama ?? '').localeCompare(b.karyawan?.nama ?? ''),
            );
        }

        return map;
    }, [jadwals]);

    const tanggalBulanIni = useMemo(
        () => semuaTanggalDiBulan(filters.bulan, filters.tahun),
        [filters.bulan, filters.tahun],
    );

    const dalamBulanIni = (tanggal: string) =>
        tanggal.startsWith(
            `${filters.tahun}-${String(filters.bulan).padStart(2, '0')}`,
        );

    const tanggalDefault = () => {
        const hariIni = tanggalHariIni();

        return dalamBulanIni(hariIni)
            ? hariIni
            : `${filters.tahun}-${String(filters.bulan).padStart(2, '0')}-01`;
    };

    const [selectedDate, setSelectedDate] = useState(tanggalDefault);
    const [periodeTerpilih, setPeriodeTerpilih] = useState({
        bulan: filters.bulan,
        tahun: filters.tahun,
    });
    const stripRef = useRef<HTMLDivElement>(null);

    // Reset ke tanggal default begitu bulan/tahun aktif berubah (mis. klik
    // panah bulan) — disesuaikan saat render, bukan lewat efek, supaya
    // tidak ada render tambahan yang tidak perlu.
    if (
        periodeTerpilih.bulan !== filters.bulan ||
        periodeTerpilih.tahun !== filters.tahun
    ) {
        setPeriodeTerpilih({ bulan: filters.bulan, tahun: filters.tahun });
        setSelectedDate(
            dalamBulanIni(selectedDate) ? selectedDate : tanggalDefault(),
        );
    }

    useEffect(() => {
        stripRef.current
            ?.querySelector<HTMLElement>(`[data-tanggal="${selectedDate}"]`)
            ?.scrollIntoView({
                behavior: 'smooth',
                inline: 'center',
                block: 'nearest',
            });
    }, [selectedDate]);

    const pilihTanggalKalender = (tanggal: string) => {
        if (!tanggal) {
            return;
        }

        const [tahunPilih, bulanPilih] = tanggal.split('-').map(Number);
        setSelectedDate(tanggal);

        if (bulanPilih !== filters.bulan || tahunPilih !== filters.tahun) {
            gantiPeriode(bulanPilih, tahunPilih);
        }
    };

    const scrollStrip = (arah: 'kiri' | 'kanan') => {
        stripRef.current?.scrollBy({
            left: arah === 'kiri' ? -240 : 240,
            behavior: 'smooth',
        });
    };

    const jadwalTanggalTerpilih = jadwalPerTanggal.get(selectedDate) ?? [];

    const [jadwalTerpilih, setJadwalTerpilih] = useState<
        Record<number, boolean>
    >({});
    const [hapusMassalProcessing, setHapusMassalProcessing] = useState(false);
    const [tanggalTerpilihUntukSeleksi, setTanggalTerpilihUntukSeleksi] =
        useState(selectedDate);

    // Reset seleksi setiap kali pindah tanggal, supaya centang di satu hari
    // tidak ikut menempel ke daftar jadwal hari lain.
    if (tanggalTerpilihUntukSeleksi !== selectedDate) {
        setTanggalTerpilihUntukSeleksi(selectedDate);
        setJadwalTerpilih({});
    }

    const jumlahJadwalTerpilih = jadwalTanggalTerpilih.filter(
        (j) => jadwalTerpilih[j.id],
    ).length;
    const semuaJadwalTerpilih =
        jadwalTanggalTerpilih.length > 0 &&
        jumlahJadwalTerpilih === jadwalTanggalTerpilih.length;

    const toggleSemuaJadwal = (checked: boolean) => {
        const next: Record<number, boolean> = {};
        jadwalTanggalTerpilih.forEach((j) => {
            next[j.id] = checked;
        });
        setJadwalTerpilih(next);
    };

    const hapusJadwalTerpilih = () => {
        const ids = jadwalTanggalTerpilih
            .filter((j) => jadwalTerpilih[j.id])
            .map((j) => j.id);

        if (ids.length === 0) {
            return;
        }

        if (
            !confirm(
                `Hapus ${ids.length} jadwal shift terpilih pada tanggal ini? Tindakan ini tidak bisa dibatalkan.`,
            )
        ) {
            return;
        }

        setHapusMassalProcessing(true);
        router.post(
            JadwalShiftController.destroyMassal.url(),
            { jadwal_shift_ids: ids },
            {
                preserveScroll: true,
                onSuccess: () => setJadwalTerpilih({}),
                onFinish: () => setHapusMassalProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Jadwal Shift" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="flex items-center gap-2 text-xl font-semibold">
                            <CalendarDays className="size-5 text-primary" />
                            Jadwal Shift
                        </h1>
                        {departemenTerkunci && departemenSaatIni && (
                            <p className="text-sm text-muted-foreground">
                                Departemen {departemenSaatIni.nama_departemen}
                            </p>
                        )}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
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
                        <Input
                            type="date"
                            value={selectedDate}
                            onChange={(e) =>
                                pilihTanggalKalender(e.target.value)
                            }
                            className="h-9 w-auto"
                            aria-label="Lompat ke tanggal"
                        />

                        {bisaPilihDepartemen && (
                            <select
                                className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm sm:w-auto"
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
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
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

                <div className="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="shrink-0"
                        onClick={() => scrollStrip('kiri')}
                    >
                        <ChevronLeft className="size-4" />
                    </Button>
                    <div
                        ref={stripRef}
                        className="flex flex-1 gap-2 overflow-x-auto scroll-smooth pb-1"
                    >
                        {tanggalBulanIni.map((tanggal) => {
                            const items = jadwalPerTanggal.get(tanggal) ?? [];
                            const isSelected = tanggal === selectedDate;
                            const isToday = tanggal === tanggalHariIni();
                            const tgl = parseTanggalLocal(tanggal);

                            return (
                                <button
                                    key={tanggal}
                                    type="button"
                                    data-tanggal={tanggal}
                                    onClick={() => setSelectedDate(tanggal)}
                                    className={cn(
                                        'flex shrink-0 flex-col items-center gap-0.5 rounded-lg border px-3 py-2 text-center transition-colors',
                                        isSelected
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-border bg-card hover:bg-accent',
                                        !isSelected &&
                                            isToday &&
                                            'border-primary/60',
                                    )}
                                >
                                    <span className="text-[10px] font-medium uppercase opacity-80">
                                        {weekdayShortFormatter.format(tgl)}
                                    </span>
                                    <span className="text-base font-semibold">
                                        {tgl.getDate()}
                                    </span>
                                    <span
                                        className={cn(
                                            'text-[10px]',
                                            items.length === 0 && 'opacity-0',
                                            isSelected
                                                ? 'text-primary-foreground/80'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {items.length || '-'} jadwal
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="shrink-0"
                        onClick={() => scrollStrip('kanan')}
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            {bisaKelola && jadwalTanggalTerpilih.length > 0 && (
                                <Checkbox
                                    checked={semuaJadwalTerpilih}
                                    onCheckedChange={(checked) =>
                                        toggleSemuaJadwal(Boolean(checked))
                                    }
                                    aria-label="Pilih semua jadwal pada tanggal ini"
                                />
                            )}
                            <CardTitle className="text-sm font-medium capitalize">
                                {weekdayFormatter.format(
                                    parseTanggalLocal(selectedDate),
                                )}
                            </CardTitle>
                        </div>
                        {jumlahJadwalTerpilih > 0 && (
                            <Button
                                size="sm"
                                variant="destructive"
                                disabled={hapusMassalProcessing}
                                onClick={hapusJadwalTerpilih}
                            >
                                Hapus {jumlahJadwalTerpilih} Terpilih
                            </Button>
                        )}
                    </CardHeader>
                    {jadwalTanggalTerpilih.length === 0 && (
                        <CardContent className="text-sm text-muted-foreground">
                            Tidak ada jadwal shift pada tanggal ini.
                        </CardContent>
                    )}
                    {jadwalTanggalTerpilih.length > 0 && (
                        <CardContent className="divide-y border-t p-0">
                            {jadwalTanggalTerpilih.map((jadwal) => (
                                <div key={jadwal.id} className="px-4 py-2.5">
                                    <div className="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex flex-wrap items-center gap-2">
                                            {bisaKelola && (
                                                <Checkbox
                                                    checked={
                                                        !!jadwalTerpilih[
                                                            jadwal.id
                                                        ]
                                                    }
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        setJadwalTerpilih(
                                                            (prev) => ({
                                                                ...prev,
                                                                [jadwal.id]:
                                                                    Boolean(
                                                                        checked,
                                                                    ),
                                                            }),
                                                        )
                                                    }
                                                    aria-label={`Pilih jadwal ${jadwal.karyawan?.nama}`}
                                                />
                                            )}
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
                    )}
                </Card>
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
