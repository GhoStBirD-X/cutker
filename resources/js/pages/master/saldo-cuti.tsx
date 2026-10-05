import { Head, router, usePage } from '@inertiajs/react';
import { Download, ListChecks, Trash2, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import SaldoCutiController from '@/actions/App/Http/Controllers/Master/SaldoCutiController';
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
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
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

type SaldoCutiRow = SaldoCuti & {
    karyawan?: Pick<Karyawan, 'id' | 'nama' | 'nip' | 'departemen'>;
    jenis_cuti?: JenisCuti;
    diubah_oleh?: Pick<Karyawan, 'id' | 'nama'>;
};

type PageProps = {
    saldoCutis: Paginated<SaldoCutiRow>;
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
        saldoCutis,
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

    // Isi halaman berganti (pindah halaman, filter, atau habis simpan) →
    // draft & pilihan lama tidak berlaku lagi. Dibandingkan per isi, bukan
    // per referensi, supaya draft tidak hilang saat simpan ditolak validasi.
    const kunciData = JSON.stringify(
        saldoCutis.data.map((r) => [r.id, r.kuota, r.terpakai, r.sisa]),
    );
    const [kunciSebelumnya, setKunciSebelumnya] = useState(kunciData);

    if (kunciSebelumnya !== kunciData) {
        setKunciSebelumnya(kunciData);
        setDrafts({});
        setSimpanErrors({});
        setDipilih(new Set());
        setSemuaFilter(false);
    }

    const rows = saldoCutis.data;
    const jumlahDraft = Object.keys(drafts).length;
    const adaSelTidakValid = Object.values(drafts).some((d) =>
        KOLOM.some((k) => !selValid(k, d[k])),
    );

    const adaDraft = jumlahDraft > 0;

    useEffect(() => {
        if (!adaDraft) {
            return;
        }

        return router.on('before', (event) => {
            const visit = (event as CustomEvent).detail?.visit;

            if (
                visit?.method === 'get' &&
                !confirm(
                    'Ada perubahan saldo yang belum disimpan. Tinggalkan tanpa menyimpan?',
                )
            ) {
                event.preventDefault();
            }
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

    const jumlahDipilih = semuaFilter ? saldoCutis.total : dipilih.size;

    const hapusSatu = (saldo: SaldoCutiRow) => {
        const label = saldo.periode_ke
            ? `periode ke-${saldo.periode_ke}`
            : `tahun ${saldo.tahun}`;

        if (
            confirm(
                `Hapus baris saldo cuti ${saldo.karyawan?.nama} · ${saldo.jenis_cuti?.nama_jenis} (${label})? Tindakan ini tidak bisa dibatalkan.`,
            )
        ) {
            router.delete(SaldoCutiController.destroy.url(saldo.id), {
                preserveScroll: true,
            });
        }
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
                            className="w-full sm:w-56"
                        >
                            <Input
                                placeholder="Cari nama/NIP..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </form>
                        <NativeSelect
                            wrapperClassName="w-full sm:w-44"
                            aria-label="Filter jenis cuti"
                            value={filters.jenis_cuti_id ?? ''}
                            onChange={(e) =>
                                terapkanFilter({
                                    jenis_cuti_id:
                                        Number(e.target.value) || null,
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
                                    departemen_id:
                                        Number(e.target.value) || null,
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
                            saldoCutis.total > rows.length && (
                                <button
                                    type="button"
                                    className="text-primary underline underline-offset-4"
                                    onClick={() => setSemuaFilter(true)}
                                >
                                    Pilih semua {saldoCutis.total} baris sesuai
                                    filter
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

                <Card className="gap-0 overflow-hidden py-0">
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full min-w-[880px] text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                    <th className="w-10 px-3 py-2">
                                        <Checkbox
                                            aria-label="Pilih semua di halaman ini"
                                            checked={
                                                semuaHalamanDipilih
                                                    ? true
                                                    : sebagianDipilih
                                                      ? 'indeterminate'
                                                      : false
                                            }
                                            onCheckedChange={(v) =>
                                                toggleSemuaHalaman(v === true)
                                            }
                                        />
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        Karyawan
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        Jenis Cuti
                                    </th>
                                    <th className="w-24 px-2 py-2 text-center font-medium">
                                        Kuota
                                    </th>
                                    <th className="w-24 px-2 py-2 text-center font-medium">
                                        Terpakai
                                    </th>
                                    <th
                                        className="w-24 px-2 py-2 text-center font-medium"
                                        title="Otomatis = kuota − terpakai, tetap bisa diubah manual"
                                    >
                                        Sisa
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        Catatan Terakhir
                                    </th>
                                    <th className="w-10 px-2 py-2" />
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {rows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="p-4 text-sm text-muted-foreground"
                                        >
                                            Tidak ada saldo cuti aktif yang
                                            cocok dengan filter.
                                        </td>
                                    </tr>
                                )}
                                {rows.map((saldo, baris) => {
                                    const diubah = saldo.id in drafts;
                                    const severity = saldoSeverity(
                                        keAngka(nilaiSel(saldo, 'sisa')),
                                        keAngka(nilaiSel(saldo, 'kuota')),
                                    );

                                    return (
                                        <tr
                                            key={saldo.id}
                                            className={cn(
                                                'transition-colors hover:bg-muted/30',
                                                dipilih.has(saldo.id) &&
                                                    'bg-primary/5',
                                                diubah &&
                                                    'bg-amber-50 hover:bg-amber-50 dark:bg-amber-950/20 dark:hover:bg-amber-950/20',
                                            )}
                                        >
                                            <td className="px-3 py-1.5">
                                                <Checkbox
                                                    aria-label={`Pilih ${saldo.karyawan?.nama}`}
                                                    checked={
                                                        semuaFilter ||
                                                        dipilih.has(saldo.id)
                                                    }
                                                    onCheckedChange={(v) =>
                                                        toggleBaris(
                                                            saldo.id,
                                                            v === true,
                                                        )
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-1.5">
                                                <div className="font-medium">
                                                    {saldo.karyawan?.nama}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {saldo.karyawan?.nip}
                                                    {saldo.karyawan
                                                        ?.departemen &&
                                                        ` · ${saldo.karyawan.departemen.nama_departemen}`}
                                                </div>
                                            </td>
                                            <td className="px-3 py-1.5">
                                                <div>
                                                    {
                                                        saldo.jenis_cuti
                                                            ?.nama_jenis
                                                    }
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
                                            </td>
                                            {KOLOM.map((kolom) => {
                                                const nilai = nilaiSel(
                                                    saldo,
                                                    kolom,
                                                );
                                                const galatServer =
                                                    simpanErrors[
                                                        `${saldo.id}.${kolom}`
                                                    ];
                                                const tidakValid =
                                                    !selValid(kolom, nilai) ||
                                                    galatServer !== undefined;
                                                const berubah =
                                                    nilai !==
                                                    nilaiAsli(saldo)[kolom];

                                                return (
                                                    <td
                                                        key={kolom}
                                                        className="px-2 py-1.5"
                                                    >
                                                        <Input
                                                            data-sel={`${baris}:${kolom}`}
                                                            inputMode="numeric"
                                                            aria-label={`${kolom} ${saldo.karyawan?.nama}`}
                                                            aria-invalid={
                                                                tidakValid
                                                            }
                                                            title={
                                                                galatServer ??
                                                                (tidakValid
                                                                    ? 'Harus bilangan bulat ≥ 0'
                                                                    : undefined)
                                                            }
                                                            placeholder={
                                                                kolom ===
                                                                'terpakai'
                                                                    ? '0'
                                                                    : '∞'
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
                                                                pindahSel(
                                                                    e,
                                                                    baris,
                                                                    kolom,
                                                                )
                                                            }
                                                            onFocus={(e) =>
                                                                e.target.select()
                                                            }
                                                            className={cn(
                                                                'h-8 text-center tabular-nums',
                                                                berubah &&
                                                                    'border-amber-400 font-semibold dark:border-amber-700',
                                                                kolom ===
                                                                    'sisa' &&
                                                                    severity.text,
                                                            )}
                                                        />
                                                    </td>
                                                );
                                            })}
                                            <td className="max-w-56 px-3 py-1.5 text-xs text-muted-foreground">
                                                {saldo.catatan ? (
                                                    <div
                                                        className="truncate"
                                                        title={`${saldo.catatan} — ${saldo.diubah_oleh?.nama ?? ''}`}
                                                    >
                                                        {saldo.catatan}
                                                        <span className="opacity-70">
                                                            {' '}
                                                            ·{' '}
                                                            {
                                                                saldo
                                                                    .diubah_oleh
                                                                    ?.nama
                                                            }
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="opacity-50">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-2 py-1.5">
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    aria-label={`Hapus saldo ${saldo.karyawan?.nama}`}
                                                    className="size-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                                    onClick={() =>
                                                        hapusSatu(saldo)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Pagination
                    links={saldoCutis.links}
                    perPage={saldoCutis.per_page}
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
