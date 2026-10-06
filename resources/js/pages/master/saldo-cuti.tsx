import { Head, router, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronsDownUp,
    ChevronsUpDown,
    Download,
    ListChecks,
    SlidersHorizontal,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import SaldoCutiController from '@/actions/App/Http/Controllers/Master/SaldoCutiController';
import { konfirmasi } from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { MasterNav } from '@/components/master-nav';
import { Pagination } from '@/components/pagination';
import { SaldoCutiAksiMassalDialog } from '@/components/saldo-cuti-aksi-massal-dialog';
import type {
    AksiMassalOption,
    SaldoCutiFilters,
} from '@/components/saldo-cuti-aksi-massal-dialog';
import { SaldoCutiImportDialog } from '@/components/saldo-cuti-import-dialog';
import { SaldoCutiTambahDialog } from '@/components/saldo-cuti-tambah-dialog';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { formatDate } from '@/lib/format';
import { saldoSeverity } from '@/lib/saldo-severity';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as saldoCutiIndex } from '@/routes/master/saldo-cuti';
import type {
    Departemen,
    JenisCuti,
    Karyawan,
    Paginated,
    SaldoCuti,
} from '@/types';

type SaldoCutiRow = Omit<SaldoCuti, 'karyawan'> & {
    karyawan?: Pick<Karyawan, 'id' | 'nama' | 'nip' | 'departemen'>;
    jenis_cuti?: JenisCuti;
    diubah_oleh?: Pick<Karyawan, 'id' | 'nama'>;
};

type GrupKaryawan = Pick<Karyawan, 'id' | 'nama' | 'nip' | 'departemen'> & {
    saldo_cutis: Omit<SaldoCutiRow, 'karyawan'>[];
};

type PageProps = {
    grupKaryawan: Paginated<GrupKaryawan>;
    totalBaris: number;
    karyawans: Pick<Karyawan, 'id' | 'nama' | 'nip' | 'departemen_id'>[];
    jenisCutis: JenisCuti[];
    departemens: Pick<Departemen, 'id' | 'nama_departemen'>[];
    tahunTersedia: number[];
    aksiMassal: AksiMassalOption[];
    filters: SaldoCutiFilters;
};

const KOLOM = ['kuota', 'terpakai', 'sisa'] as const;
type Kolom = (typeof KOLOM)[number];
type Draft = Record<Kolom, string>;

const keString = (nilai: number | null) =>
    nilai === null ? '' : String(nilai);

const nilaiAsli = (saldo: SaldoCutiRow): Draft => ({
    kuota: keString(saldo.kuota),
    terpakai: String(saldo.terpakai),
    sisa: keString(saldo.sisa),
});

/** Kuota & sisa boleh kosong (= tanpa batas); terpakai wajib. */
const selValid = (kolom: Kolom, nilai: string): boolean => {
    if (nilai === '') {
        return kolom !== 'terpakai';
    }

    const angka = Number(nilai);

    return (
        Number.isInteger(angka) &&
        angka >= 0 &&
        (kolom !== 'kuota' || angka <= 365)
    );
};

const keAngka = (nilai: string): number | null =>
    nilai === '' ? null : Number(nilai);

export default function MasterSaldoCuti() {
    const {
        grupKaryawan,
        totalBaris,
        karyawans,
        jenisCutis,
        departemens,
        tahunTersedia,
        aksiMassal,
        filters,
    } = usePage<PageProps>().props;

    const [search, setSearch] = useState(filters.search ?? '');
    const [drafts, setDrafts] = useState<Record<number, Draft>>({});
    const [catatan, setCatatan] = useState('');
    const [simpanErrors, setSimpanErrors] = useState<Record<string, string>>(
        {},
    );
    const [menyimpan, setMenyimpan] = useState(false);
    const [dipilih, setDipilih] = useState<Set<number>>(new Set());
    const [semuaFilter, setSemuaFilter] = useState(false);
    const [aksiOpen, setAksiOpen] = useState(false);
    const [filterOpen, setFilterOpen] = useState(false);
    // Di layar sempit grup dimulai tertutup supaya daftar nama mudah
    // dipindai; di desktop langsung terbuka seperti tabel biasa.
    const [bawaanTerbuka, setBawaanTerbuka] = useState(
        () => !window.matchMedia('(max-width: 639px)').matches,
    );
    const [grupDibalik, setGrupDibalik] = useState<Set<number>>(new Set());
    const grupTerbuka = (id: number) => bawaanTerbuka !== grupDibalik.has(id);

    /** Baris saldo diratakan (karyawan disisipkan) untuk pilihan, draft & label. */
    const rows: SaldoCutiRow[] = grupKaryawan.data.flatMap((grup) =>
        grup.saldo_cutis.map((saldo) => ({ ...saldo, karyawan: grup })),
    );

    // Isi halaman berganti (pindah halaman, filter, atau habis simpan) →
    // draft & pilihan lama tidak berlaku lagi. Dibandingkan per isi, bukan
    // per referensi, supaya draft tidak hilang saat simpan ditolak validasi.
    const kunciData = JSON.stringify(
        rows.map((r) => [r.id, r.kuota, r.terpakai, r.sisa]),
    );
    const [kunciSebelumnya, setKunciSebelumnya] = useState(kunciData);

    if (kunciSebelumnya !== kunciData) {
        setKunciSebelumnya(kunciData);
        setDrafts({});
        setSimpanErrors({});
        setDipilih(new Set());
        setSemuaFilter(false);
    }

    const jumlahDraft = Object.keys(drafts).length;
    const adaSelTidakValid = Object.values(drafts).some((d) =>
        KOLOM.some((k) => !selValid(k, d[k])),
    );

    const adaDraft = jumlahDraft > 0;
    const lewatiPenjagaDraft = useRef(false);

    useEffect(() => {
        if (!adaDraft) {
            return;
        }

        return router.on('before', (event) => {
            const visit = (event as CustomEvent).detail?.visit;

            if (visit?.method !== 'get' || lewatiPenjagaDraft.current) {
                return;
            }

            event.preventDefault();

            void konfirmasi({
                title: 'Tinggalkan tanpa menyimpan?',
                description: 'Ada perubahan saldo yang belum disimpan.',
                confirmText: 'Tinggalkan',
                cancelText: 'Tetap di sini',
                destructive: true,
            }).then((tinggalkan) => {
                if (!tinggalkan) {
                    return;
                }

                lewatiPenjagaDraft.current = true;
                router.visit(visit.url, {
                    preserveState: visit.preserveState,
                    preserveScroll: visit.preserveScroll,
                    onFinish: () => {
                        lewatiPenjagaDraft.current = false;
                    },
                });
            });
        });
    }, [adaDraft]);

    const nilaiSel = (saldo: SaldoCutiRow, kolom: Kolom): string =>
        drafts[saldo.id]?.[kolom] ?? nilaiAsli(saldo)[kolom];

    const ubahSel = (saldo: SaldoCutiRow, kolom: Kolom, nilai: string) => {
        const asli = nilaiAsli(saldo);
        const draft: Draft = { ...(drafts[saldo.id] ?? asli), [kolom]: nilai };

        // Sisa ikut dihitung ulang dari kuota − terpakai, tapi tetap bisa
        // ditimpa manual sesudahnya (mis. ada potongan cuti massal).
        if (kolom !== 'sisa') {
            if (draft.kuota === '') {
                draft.sisa = '';
            } else if (selValid('terpakai', draft.terpakai)) {
                draft.sisa = String(
                    Number(draft.kuota) - Number(draft.terpakai),
                );
            }
        }

        setDrafts((prev) => {
            const next = { ...prev };

            if (KOLOM.every((k) => draft[k] === asli[k])) {
                delete next[saldo.id];
            } else {
                next[saldo.id] = draft;
            }

            return next;
        });
    };

    const pindahSel = (
        e: React.KeyboardEvent<HTMLInputElement>,
        baris: number,
        kolom: Kolom,
    ) => {
        const arah =
            e.key === 'Enter' || e.key === 'ArrowDown'
                ? 1
                : e.key === 'ArrowUp'
                  ? -1
                  : 0;

        if (arah === 0) {
            return;
        }

        e.preventDefault();
        const target = document.querySelector<HTMLInputElement>(
            `[data-sel="${baris + arah}:${kolom}"]`,
        );
        target?.focus();
        target?.select();
    };

    const batalkanDraft = () => {
        setDrafts({});
        setSimpanErrors({});
    };

    const simpan = () => {
        const ids = Object.keys(drafts).map(Number);

        router.put(
            SaldoCutiController.updateMassal.url(),
            {
                perubahan: ids.map((id) => ({
                    id,
                    kuota: keAngka(drafts[id].kuota),
                    terpakai: Number(drafts[id].terpakai),
                    sisa: keAngka(drafts[id].sisa),
                })),
                catatan,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setMenyimpan(true),
                onFinish: () => setMenyimpan(false),
                onError: (errors) => {
                    // Kunci galat server "perubahan.N.kolom" → id baris.
                    const perBaris: Record<string, string> = {};

                    Object.entries(errors).forEach(([kunci, pesan]) => {
                        const cocok = kunci.match(/^perubahan\.(\d+)\.(\w+)$/);
                        perBaris[
                            cocok
                                ? `${ids[Number(cocok[1])]}.${cocok[2]}`
                                : kunci
                        ] = pesan;
                    });
                    setSimpanErrors(perBaris);
                },
                onSuccess: () => setCatatan(''),
            },
        );
    };

    const terapkanFilter = (perubahan: Partial<SaldoCutiFilters>) => {
        const gabungan = { ...filters, search, ...perubahan };
        const perPage = new URLSearchParams(window.location.search).get(
            'per_page',
        );

        router.get(
            saldoCutiIndex.url(),
            Object.fromEntries(
                Object.entries({ ...gabungan, per_page: perPage }).filter(
                    ([, v]) => v !== null && v !== '',
                ),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const toggleBaris = (id: number, pilih: boolean) => {
        setSemuaFilter(false);
        setDipilih((prev) => {
            const next = new Set(prev);

            if (pilih) {
                next.add(id);
            } else {
                next.delete(id);
            }

            return next;
        });
    };

    const semuaHalamanDipilih =
        rows.length > 0 && rows.every((r) => dipilih.has(r.id));
    const sebagianDipilih = !semuaHalamanDipilih && dipilih.size > 0;

    const toggleSemuaHalaman = (pilih: boolean) => {
        setSemuaFilter(false);
        setDipilih(pilih ? new Set(rows.map((r) => r.id)) : new Set());
    };

    const jumlahDipilih = semuaFilter ? totalBaris : dipilih.size;

    const toggleGrupDipilih = (grup: GrupKaryawan, pilih: boolean) => {
        setSemuaFilter(false);
        setDipilih((prev) => {
            const next = new Set(prev);
            grup.saldo_cutis.forEach((s) =>
                pilih ? next.add(s.id) : next.delete(s.id),
            );

            return next;
        });
    };

    const toggleGrupTerbuka = (id: number) =>
        setGrupDibalik((prev) => {
            const next = new Set(prev);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });

    const aturSemuaGrup = (terbuka: boolean) => {
        setBawaanTerbuka(terbuka);
        setGrupDibalik(new Set());
    };

    const adaGrupTerbuka = grupKaryawan.data.some((g) => grupTerbuka(g.id));

    const jumlahFilterAktif = [
        filters.jenis_cuti_id,
        filters.departemen_id,
        filters.tahun,
    ].filter((v) => v !== null).length;

    /** Urutan sel untuk navigasi Enter/↑/↓ — hanya baris di grup yang terbuka. */
    const urutanBaris = new Map(
        rows.filter((r) => grupTerbuka(r.karyawan_id)).map((r, i) => [r.id, i]),
    );

    const hapusSatu = async (saldo: SaldoCutiRow) => {
        const label = saldo.periode_ke
            ? `periode ke-${saldo.periode_ke}`
            : `tahun ${saldo.tahun}`;

        if (
            await konfirmasi({
                title: 'Hapus baris saldo cuti?',
                description: `${saldo.karyawan?.nama} · ${saldo.jenis_cuti?.nama_jenis} (${label}). Tindakan ini tidak bisa dibatalkan.`,
                confirmText: 'Hapus',
                destructive: true,
            })
        ) {
            router.delete(SaldoCutiController.destroy.url(saldo.id), {
                preserveScroll: true,
            });
        }
    };

    const pilihanFilter = (
        <>
            <NativeSelect
                wrapperClassName="w-full sm:w-44"
                aria-label="Filter jenis cuti"
                value={filters.jenis_cuti_id ?? ''}
                onChange={(e) =>
                    terapkanFilter({
                        jenis_cuti_id: Number(e.target.value) || null,
                    })
                }
            >
                <option value="">Semua jenis cuti</option>
                {jenisCutis.map((j) => (
                    <option key={j.id} value={j.id}>
                        {j.nama_jenis}
                    </option>
                ))}
            </NativeSelect>
            <NativeSelect
                wrapperClassName="w-full sm:w-44"
                aria-label="Filter departemen"
                value={filters.departemen_id ?? ''}
                onChange={(e) =>
                    terapkanFilter({
                        departemen_id: Number(e.target.value) || null,
                    })
                }
            >
                <option value="">Semua departemen</option>
                {departemens.map((d) => (
                    <option key={d.id} value={d.id}>
                        {d.nama_departemen}
                    </option>
                ))}
            </NativeSelect>
            <NativeSelect
                wrapperClassName="w-full sm:w-32"
                aria-label="Filter tahun"
                value={filters.tahun ?? ''}
                onChange={(e) =>
                    terapkanFilter({
                        tahun: Number(e.target.value) || null,
                    })
                }
            >
                <option value="">Semua tahun</option>
                {tahunTersedia.map((t) => (
                    <option key={t} value={t}>
                        {t}
                    </option>
                ))}
            </NativeSelect>
        </>
    );

    const LABEL_KOLOM: Record<Kolom, string> = {
        kuota: 'Kuota',
        terpakai: 'Terpakai',
        sisa: 'Sisa',
    };

    /**
     * Satu jenis cuti di dalam grup karyawan. Di layar sempit tampil
     * bertumpuk (nama jenis di atas, tiga input berdampingan di bawah);
     * mulai `sm` berubah jadi kolom sejajar seperti tabel.
     */
    const renderBarisSaldo = (saldo: SaldoCutiRow) => {
        const diubah = saldo.id in drafts;
        const baris = urutanBaris.get(saldo.id) ?? -1;
        const severity = saldoSeverity(
            keAngka(nilaiSel(saldo, 'sisa')),
            keAngka(nilaiSel(saldo, 'kuota')),
        );

        return (
            <div
                key={saldo.id}
                className={cn(
                    'grid grid-cols-[1.5rem_minmax(0,1fr)_2rem] items-center gap-x-2 gap-y-2 px-3 py-2 transition-colors hover:bg-muted/30 sm:grid-cols-[1.5rem_minmax(0,1fr)_5.5rem_5.5rem_5.5rem_minmax(0,14rem)_2rem] sm:py-1.5',
                    dipilih.has(saldo.id) && 'bg-primary/5',
                    diubah &&
                        'bg-amber-50 hover:bg-amber-50 dark:bg-amber-950/20 dark:hover:bg-amber-950/20',
                )}
            >
                <Checkbox
                    aria-label={`Pilih ${saldo.karyawan?.nama} · ${saldo.jenis_cuti?.nama_jenis}`}
                    checked={semuaFilter || dipilih.has(saldo.id)}
                    onCheckedChange={(v) => toggleBaris(saldo.id, v === true)}
                />
                <div className="min-w-0">
                    <div className="truncate">
                        {saldo.jenis_cuti?.nama_jenis}
                    </div>
                    <div
                        className="text-xs text-muted-foreground"
                        title={
                            saldo.periode_ke
                                ? `${formatDate(saldo.periode_mulai!)} s/d ${formatDate(saldo.periode_selesai!)}`
                                : undefined
                        }
                    >
                        {saldo.periode_ke
                            ? `Periode ke-${saldo.periode_ke}`
                            : `Tahun ${saldo.tahun}`}
                    </div>
                </div>
                <div className="col-span-3 col-start-1 row-start-2 grid grid-cols-3 gap-2 sm:col-span-3 sm:col-start-3 sm:row-start-1">
                    {KOLOM.map((kolom) => {
                        const nilai = nilaiSel(saldo, kolom);
                        const galatServer =
                            simpanErrors[`${saldo.id}.${kolom}`];
                        const tidakValid =
                            !selValid(kolom, nilai) ||
                            galatServer !== undefined;
                        const berubah = nilai !== nilaiAsli(saldo)[kolom];

                        return (
                            <label key={kolom} className="flex flex-col gap-1">
                                <span className="text-xs text-muted-foreground sm:sr-only">
                                    {LABEL_KOLOM[kolom]}
                                </span>
                                <Input
                                    data-sel={`${baris}:${kolom}`}
                                    inputMode="numeric"
                                    aria-label={`${kolom} ${saldo.karyawan?.nama} ${saldo.jenis_cuti?.nama_jenis}`}
                                    aria-invalid={tidakValid}
                                    title={
                                        galatServer ??
                                        (tidakValid
                                            ? 'Harus bilangan bulat ≥ 0'
                                            : undefined)
                                    }
                                    placeholder={
                                        kolom === 'terpakai' ? '0' : '∞'
                                    }
                                    value={nilai}
                                    onChange={(e) =>
                                        ubahSel(
                                            saldo,
                                            kolom,
                                            e.target.value.trim(),
                                        )
                                    }
                                    onKeyDown={(e) =>
                                        pindahSel(e, baris, kolom)
                                    }
                                    onFocus={(e) => e.target.select()}
                                    className={cn(
                                        'h-9 text-center tabular-nums sm:h-8',
                                        berubah &&
                                            'border-amber-400 font-semibold dark:border-amber-700',
                                        kolom === 'sisa' && severity.text,
                                    )}
                                />
                            </label>
                        );
                    })}
                </div>
                <div className="col-span-3 col-start-1 row-start-3 min-w-0 text-xs text-muted-foreground sm:col-span-1 sm:col-start-6 sm:row-start-1">
                    {saldo.catatan ? (
                        <div
                            className="truncate"
                            title={`${saldo.catatan} — ${saldo.diubah_oleh?.nama ?? ''}`}
                        >
                            {saldo.catatan}
                            <span className="opacity-70">
                                {' '}
                                · {saldo.diubah_oleh?.nama}
                            </span>
                        </div>
                    ) : (
                        <span className="hidden opacity-50 sm:inline">—</span>
                    )}
                </div>
                <Button
                    size="icon"
                    variant="ghost"
                    aria-label={`Hapus saldo ${saldo.karyawan?.nama} · ${saldo.jenis_cuti?.nama_jenis}`}
                    className="col-start-3 row-start-1 size-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive sm:col-start-7"
                    onClick={() => hapusSatu(saldo)}
                >
                    <Trash2 className="size-4" />
                </Button>
            </div>
        );
    };

    return (
        <>
            <Head title="Master Saldo Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Master Saldo Cuti</h1>
                    <p className="text-sm text-muted-foreground">
                        Ketik langsung di kolom kuota/terpakai/sisa (Enter atau
                        ↑/↓ untuk pindah baris), lalu simpan semua perubahan
                        sekaligus. Setiap koreksi wajib disertai catatan.
                    </p>
                </div>

                <MasterNav />

                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div className="flex flex-1 flex-wrap items-center gap-2">
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                terapkanFilter({ search });
                            }}
                            className="min-w-0 flex-1 sm:w-56 sm:flex-none"
                        >
                            <Input
                                type="search"
                                placeholder="Cari nama/NPK..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </form>
                        <Sheet open={filterOpen} onOpenChange={setFilterOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    variant="outline"
                                    className="gap-1.5 sm:hidden"
                                >
                                    <SlidersHorizontal className="size-4" />
                                    Filter
                                    {jumlahFilterAktif > 0 && (
                                        <span className="rounded-full bg-primary px-1.5 text-xs text-primary-foreground tabular-nums">
                                            {jumlahFilterAktif}
                                        </span>
                                    )}
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="bottom"
                                className="rounded-t-xl pb-6"
                            >
                                <SheetHeader>
                                    <SheetTitle>Filter saldo cuti</SheetTitle>
                                    <SheetDescription>
                                        Filter langsung diterapkan saat dipilih.
                                    </SheetDescription>
                                </SheetHeader>
                                <div className="flex flex-col gap-3 px-4">
                                    {pilihanFilter}
                                    {jumlahFilterAktif > 0 && (
                                        <Button
                                            variant="ghost"
                                            onClick={() =>
                                                terapkanFilter({
                                                    jenis_cuti_id: null,
                                                    departemen_id: null,
                                                    tahun: null,
                                                })
                                            }
                                        >
                                            Reset filter
                                        </Button>
                                    )}
                                </div>
                            </SheetContent>
                        </Sheet>
                        <div className="hidden flex-wrap items-center gap-2 sm:flex">
                            {pilihanFilter}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" className="gap-1.5">
                            <a
                                href={SaldoCutiController.exportMethod.url({
                                    query: Object.fromEntries(
                                        Object.entries(filters).filter(
                                            ([, v]) => v !== null && v !== '',
                                        ),
                                    ),
                                })}
                            >
                                <Download className="size-4" />
                                Export
                            </a>
                        </Button>
                        <SaldoCutiImportDialog filters={filters} />
                        <SaldoCutiTambahDialog
                            karyawans={karyawans}
                            jenisCutis={jenisCutis}
                            departemens={departemens}
                        />
                    </div>
                </div>

                {jumlahDipilih > 0 && (
                    <div className="flex flex-wrap items-center gap-2 rounded-md border border-primary/30 bg-primary/5 px-3 py-2 text-sm">
                        <ListChecks className="size-4 text-primary" />
                        <span className="font-medium">
                            {jumlahDipilih} baris dipilih
                            {semuaFilter && ' (semua hasil filter)'}
                        </span>
                        {semuaHalamanDipilih &&
                            !semuaFilter &&
                            totalBaris > rows.length && (
                                <button
                                    type="button"
                                    className="text-primary underline underline-offset-4"
                                    onClick={() => setSemuaFilter(true)}
                                >
                                    Pilih semua {totalBaris} baris sesuai filter
                                </button>
                            )}
                        <div className="ml-auto flex gap-2">
                            <Button size="sm" onClick={() => setAksiOpen(true)}>
                                Aksi Massal…
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                className="gap-1"
                                onClick={() => toggleSemuaHalaman(false)}
                            >
                                <X className="size-4" />
                                Batal pilih
                            </Button>
                        </div>
                    </div>
                )}

                <div className="flex items-center gap-3 px-1 text-sm text-muted-foreground">
                    <Checkbox
                        aria-label="Pilih semua di halaman ini"
                        checked={
                            semuaHalamanDipilih
                                ? true
                                : sebagianDipilih
                                  ? 'indeterminate'
                                  : false
                        }
                        onCheckedChange={(v) => toggleSemuaHalaman(v === true)}
                        disabled={rows.length === 0}
                    />
                    <span>
                        {grupKaryawan.total} karyawan · {totalBaris} baris saldo
                    </span>
                    {grupKaryawan.data.length > 0 && (
                        <Button
                            size="sm"
                            variant="ghost"
                            className="ml-auto h-8 gap-1.5"
                            onClick={() => aturSemuaGrup(!adaGrupTerbuka)}
                        >
                            {adaGrupTerbuka ? (
                                <ChevronsDownUp className="size-4" />
                            ) : (
                                <ChevronsUpDown className="size-4" />
                            )}
                            {adaGrupTerbuka ? 'Tutup semua' : 'Buka semua'}
                        </Button>
                    )}
                </div>

                {grupKaryawan.data.length === 0 && (
                    <Card className="p-4 text-sm text-muted-foreground">
                        Tidak ada saldo cuti aktif yang cocok dengan filter.
                    </Card>
                )}

                <div className="flex flex-col gap-3">
                    {grupKaryawan.data.map((grup) => {
                        const terbuka = grupTerbuka(grup.id);
                        const barisGrup = rows.filter(
                            (r) => r.karyawan_id === grup.id,
                        );
                        const jumlahGrupDipilih = barisGrup.filter((r) =>
                            dipilih.has(r.id),
                        ).length;
                        const jumlahGrupDiubah = barisGrup.filter(
                            (r) => r.id in drafts,
                        ).length;

                        return (
                            <Card
                                key={grup.id}
                                className={cn(
                                    'gap-0 overflow-hidden py-0',
                                    jumlahGrupDiubah > 0 &&
                                        'border-amber-300 dark:border-amber-800',
                                )}
                            >
                                <div className="flex items-start gap-3 px-3 py-2.5">
                                    <Checkbox
                                        className="mt-0.5"
                                        aria-label={`Pilih semua saldo ${grup.nama}`}
                                        checked={
                                            semuaFilter ||
                                            (jumlahGrupDipilih > 0 &&
                                                jumlahGrupDipilih ===
                                                    barisGrup.length)
                                                ? true
                                                : jumlahGrupDipilih > 0
                                                  ? 'indeterminate'
                                                  : false
                                        }
                                        onCheckedChange={(v) =>
                                            toggleGrupDipilih(grup, v === true)
                                        }
                                    />
                                    <button
                                        type="button"
                                        className="flex min-w-0 flex-1 items-start gap-2 text-left"
                                        aria-expanded={terbuka}
                                        onClick={() =>
                                            toggleGrupTerbuka(grup.id)
                                        }
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate font-medium">
                                                {grup.nama}
                                                {jumlahGrupDiubah > 0 && (
                                                    <span className="ml-2 text-xs font-normal text-amber-700 dark:text-amber-400">
                                                        {jumlahGrupDiubah}{' '}
                                                        diubah
                                                    </span>
                                                )}
                                            </div>
                                            <div className="truncate text-xs text-muted-foreground">
                                                {grup.nip}
                                                {grup.departemen &&
                                                    ` · ${grup.departemen.nama_departemen}`}
                                            </div>
                                            {!terbuka && (
                                                <div className="mt-1.5 flex flex-wrap gap-1">
                                                    {barisGrup.map((saldo) => {
                                                        const sisa = keAngka(
                                                            nilaiSel(
                                                                saldo,
                                                                'sisa',
                                                            ),
                                                        );
                                                        const kuota = keAngka(
                                                            nilaiSel(
                                                                saldo,
                                                                'kuota',
                                                            ),
                                                        );

                                                        return (
                                                            <span
                                                                key={saldo.id}
                                                                className={cn(
                                                                    'rounded-md border px-1.5 py-0.5 text-xs tabular-nums',
                                                                    saldoSeverity(
                                                                        sisa,
                                                                        kuota,
                                                                    ).badge,
                                                                )}
                                                            >
                                                                {
                                                                    saldo
                                                                        .jenis_cuti
                                                                        ?.nama_jenis
                                                                }{' '}
                                                                {sisa === null
                                                                    ? '∞'
                                                                    : `${sisa}/${kuota}`}
                                                            </span>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        </div>
                                        <span className="mt-0.5 shrink-0 text-xs text-muted-foreground">
                                            {barisGrup.length} jenis
                                        </span>
                                        <ChevronDown
                                            className={cn(
                                                'mt-0.5 size-4 shrink-0 text-muted-foreground transition-transform',
                                                terbuka && 'rotate-180',
                                            )}
                                        />
                                    </button>
                                </div>

                                {terbuka && (
                                    <div className="border-t">
                                        <div className="hidden grid-cols-[1.5rem_minmax(0,1fr)_5.5rem_5.5rem_5.5rem_minmax(0,14rem)_2rem] gap-x-2 bg-muted/40 px-3 py-1.5 text-xs text-muted-foreground sm:grid">
                                            <span />
                                            <span>Jenis Cuti</span>
                                            <span className="text-center">
                                                Kuota
                                            </span>
                                            <span className="text-center">
                                                Terpakai
                                            </span>
                                            <span
                                                className="text-center"
                                                title="Otomatis = kuota − terpakai, tetap bisa diubah manual"
                                            >
                                                Sisa
                                            </span>
                                            <span>Catatan Terakhir</span>
                                            <span />
                                        </div>
                                        <div className="divide-y">
                                            {barisGrup.map((saldo) =>
                                                renderBarisSaldo(saldo),
                                            )}
                                        </div>
                                    </div>
                                )}
                            </Card>
                        );
                    })}
                </div>

                <Pagination
                    links={grupKaryawan.links}
                    perPage={grupKaryawan.per_page}
                />

                {jumlahDraft > 0 && (
                    <div className="sticky bottom-4 z-10 flex flex-wrap items-center gap-3 rounded-lg border border-amber-300 bg-background/95 p-3 shadow-lg backdrop-blur dark:border-amber-800">
                        <span className="text-sm font-medium">
                            {jumlahDraft} baris diubah
                        </span>
                        <div className="min-w-56 flex-1">
                            <Input
                                placeholder="Catatan koreksi (wajib), mis. Migrasi saldo dari sistem lama"
                                value={catatan}
                                onChange={(e) => setCatatan(e.target.value)}
                                aria-invalid={!!simpanErrors.catatan}
                            />
                            <InputError
                                message={
                                    simpanErrors.catatan ??
                                    (adaSelTidakValid
                                        ? 'Perbaiki sel yang ditandai merah.'
                                        : Object.keys(simpanErrors).length > 0
                                          ? 'Ada nilai yang ditolak server — arahkan kursor ke sel merah untuk detail.'
                                          : undefined)
                                }
                            />
                        </div>
                        <Button
                            variant="outline"
                            onClick={batalkanDraft}
                            disabled={menyimpan}
                        >
                            Batalkan
                        </Button>
                        <Button
                            onClick={simpan}
                            disabled={
                                menyimpan ||
                                adaSelTidakValid ||
                                catatan.trim() === ''
                            }
                        >
                            Simpan {jumlahDraft} perubahan
                        </Button>
                    </div>
                )}
            </div>

            <SaldoCutiAksiMassalDialog
                open={aksiOpen}
                onOpenChange={setAksiOpen}
                ids={semuaFilter ? null : [...dipilih]}
                jumlah={jumlahDipilih}
                filters={filters}
                aksiOptions={aksiMassal}
                onSelesai={() => toggleSemuaHalaman(false)}
            />
        </>
    );
}

MasterSaldoCuti.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Master Saldo Cuti', href: saldoCutiIndex() },
    ],
};
