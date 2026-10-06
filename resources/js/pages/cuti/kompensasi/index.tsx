import { Head, router, usePage } from '@inertiajs/react';
import { FileClock } from 'lucide-react';
import { useState } from 'react';
import KompensasiCutiController from '@/actions/App/Http/Controllers/Cuti/KompensasiCutiController';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { formatDate, formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as kompensasiIndex } from '@/routes/cuti/kompensasi';
import type {
    Departemen,
    JenisCuti,
    KompensasiCuti,
    Paginated,
    StatusKompensasiCuti,
} from '@/types';

type Filters = {
    status: StatusKompensasiCuti;
    search: string;
    departemen_id: number | null;
    jenis_cuti_id: number | null;
};

type PageProps = {
    kompensasiCutis: Paginated<KompensasiCuti>;
    jumlahMenunggu: number;
    jumlahDiproses: number;
    departemens: Pick<Departemen, 'id' | 'nama_departemen'>[];
    jenisCutis: Pick<JenisCuti, 'id' | 'nama_jenis'>[];
    filters: Filters;
};

function formatRupiah(value: string | number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

const rateValid = (rate: string) => rate !== '' && Number(rate) >= 0;

function Periode({ kompensasi }: { kompensasi: KompensasiCuti }) {
    const riwayat = kompensasi.riwayat_saldo_cuti;

    return (
        <>
            <div>{kompensasi.jenis_cuti?.nama_jenis}</div>
            {riwayat && (
                <div
                    className="text-xs text-muted-foreground"
                    title={`${formatDate(riwayat.periode_mulai)} s/d ${formatDate(riwayat.periode_selesai)}`}
                >
                    Periode ke-{riwayat.periode_ke} · s/d{' '}
                    {formatDate(riwayat.periode_selesai)}
                </div>
            )}
        </>
    );
}

function Karyawan({ kompensasi }: { kompensasi: KompensasiCuti }) {
    return (
        <>
            <div className="font-medium">{kompensasi.karyawan?.nama}</div>
            <div className="text-xs text-muted-foreground">
                {kompensasi.karyawan?.nip}
                {kompensasi.karyawan?.departemen &&
                    ` · ${kompensasi.karyawan.departemen.nama_departemen}`}
            </div>
        </>
    );
}

export default function KompensasiCutiIndex() {
    const {
        kompensasiCutis,
        jumlahMenunggu,
        jumlahDiproses,
        departemens,
        jenisCutis,
        filters,
    } = usePage<PageProps>().props;

    const rows = kompensasiCutis.data;
    const tabMenunggu = filters.status === 'menunggu_diproses';

    const [search, setSearch] = useState(filters.search);
    const [rates, setRates] = useState<Record<number, string>>({});
    const [dipilih, setDipilih] = useState<Set<number>>(new Set());
    const [rateMassal, setRateMassal] = useState('');
    const [catatan, setCatatan] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    // Isi halaman berganti (filter, pindah halaman, habis proses) → rate
    // & pilihan lama tidak berlaku lagi.
    const kunciData = rows.map((r) => r.id).join(',');
    const [kunciSebelumnya, setKunciSebelumnya] = useState(kunciData);

    if (kunciSebelumnya !== kunciData) {
        setKunciSebelumnya(kunciData);
        setRates({});
        setDipilih(new Set());
        setErrors({});
    }

    const siapDiproses = rows.filter((r) => rateValid(rates[r.id] ?? ''));
    const totalRupiah = siapDiproses.reduce(
        (total, r) => total + Number(rates[r.id]) * r.jumlah_hari,
        0,
    );
    const semuaDipilih = rows.length > 0 && dipilih.size === rows.length;

    const terapkanFilter = (perubahan: Partial<Filters>) => {
        const perPage = new URLSearchParams(window.location.search).get(
            'per_page',
        );

        router.get(
            kompensasiIndex.url(),
            Object.fromEntries(
                Object.entries({
                    ...filters,
                    search,
                    per_page: 'status' in perubahan ? null : perPage,
                    ...perubahan,
                }).filter(([, v]) => v !== null && v !== ''),
            ),
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const toggle = (id: number, nilai: boolean) => {
        setDipilih((prev) => {
            const next = new Set(prev);

            if (nilai) {
                next.add(id);
            } else {
                next.delete(id);
            }

            return next;
        });
    };

    const isiRateKeDipilih = () => {
        setRates((prev) => {
            const next = { ...prev };
            dipilih.forEach((id) => {
                next[id] = rateMassal;
            });

            return next;
        });
    };

    const pindahBaris = (
        e: React.KeyboardEvent<HTMLInputElement>,
        baris: number,
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
        document
            .querySelector<HTMLInputElement>(`[data-rate="${baris + arah}"]`)
            ?.focus();
    };

    const proses = () => {
        const items = siapDiproses.map((r) => ({
            id: r.id,
            rate_per_hari: rates[r.id],
        }));

        if (
            !confirm(
                `Proses ${items.length} kompensasi dengan total ${formatRupiah(totalRupiah)}?`,
            )
        ) {
            return;
        }

        router.post(
            KompensasiCutiController.prosesMassal.url(),
            { items, catatan: catatan || undefined },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (err) => {
                    const perBaris: Record<string, string> = {};

                    Object.entries(err).forEach(([kunci, pesan]) => {
                        const cocok = kunci.match(/^items\.(\d+)\./);
                        perBaris[
                            cocok ? String(items[Number(cocok[1])].id) : kunci
                        ] = pesan;
                    });
                    setErrors(perBaris);
                },
                onSuccess: () => setCatatan(''),
            },
        );
    };

    return (
        <>
            <Head title="Kompensasi Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="flex items-center gap-2 text-xl font-semibold">
                        <FileClock className="size-5 text-amber-600 dark:text-amber-400" />
                        Kompensasi Cuti
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Sisa cuti tahunan/besar yang hangus saat periode
                        ditutup. Ketik rate per hari langsung di tabel (Enter
                        untuk baris berikutnya), atau centang beberapa baris dan
                        isi rate yang sama sekaligus.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <div className="flex rounded-md border p-0.5">
                        {(
                            [
                                [
                                    'menunggu_diproses',
                                    'Menunggu',
                                    jumlahMenunggu,
                                ],
                                ['diproses', 'Sudah diproses', jumlahDiproses],
                            ] as const
                        ).map(([status, label, jumlah]) => (
                            <button
                                key={status}
                                type="button"
                                onClick={() => terapkanFilter({ status })}
                                className={cn(
                                    'rounded px-3 py-1.5 text-sm font-medium transition-colors',
                                    filters.status === status
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted',
                                )}
                            >
                                {label} ({jumlah})
                            </button>
                        ))}
                    </div>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            terapkanFilter({ search });
                        }}
                        className="w-full sm:w-56"
                    >
                        <Input
                            placeholder="Cari nama/NPK..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </form>
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
                        wrapperClassName="w-full sm:w-40"
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
                </div>

                {tabMenunggu && dipilih.size > 0 && (
                    <div className="flex flex-wrap items-center gap-2 rounded-md border border-primary/30 bg-primary/5 px-3 py-2 text-sm">
                        <span className="font-medium">
                            {dipilih.size} dipilih — isi rate yang sama:
                        </span>
                        <Input
                            type="number"
                            min={0}
                            placeholder="Rp / hari"
                            className="h-8 w-36"
                            value={rateMassal}
                            onChange={(e) => setRateMassal(e.target.value)}
                            onKeyDown={(e) => {
                                if (
                                    e.key === 'Enter' &&
                                    rateValid(rateMassal)
                                ) {
                                    isiRateKeDipilih();
                                }
                            }}
                        />
                        <Button
                            size="sm"
                            disabled={!rateValid(rateMassal)}
                            onClick={isiRateKeDipilih}
                        >
                            Isi ke {dipilih.size} baris
                        </Button>
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => setDipilih(new Set())}
                        >
                            Batal pilih
                        </Button>
                    </div>
                )}

                <Card className="gap-0 overflow-hidden py-0">
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full min-w-[760px] text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40 text-left text-xs text-muted-foreground">
                                    {tabMenunggu && (
                                        <th className="w-10 px-3 py-2">
                                            <Checkbox
                                                aria-label="Pilih semua di halaman ini"
                                                checked={semuaDipilih}
                                                onCheckedChange={(v) =>
                                                    setDipilih(
                                                        v === true
                                                            ? new Set(
                                                                  rows.map(
                                                                      (r) =>
                                                                          r.id,
                                                                  ),
                                                              )
                                                            : new Set(),
                                                    )
                                                }
                                            />
                                        </th>
                                    )}
                                    <th className="px-3 py-2 font-medium">
                                        Karyawan
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        Jenis / Periode
                                    </th>
                                    <th className="w-20 px-3 py-2 text-center font-medium">
                                        Hari
                                    </th>
                                    <th className="w-40 px-3 py-2 font-medium">
                                        Rate / hari
                                    </th>
                                    <th className="w-36 px-3 py-2 text-right font-medium">
                                        Total
                                    </th>
                                    {!tabMenunggu && (
                                        <th className="px-3 py-2 font-medium">
                                            Diproses
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {rows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="p-4 text-sm text-muted-foreground"
                                        >
                                            {tabMenunggu
                                                ? 'Tidak ada kompensasi yang menunggu diproses.'
                                                : 'Belum ada kompensasi yang diproses.'}
                                        </td>
                                    </tr>
                                )}
                                {tabMenunggu &&
                                    rows.map((k, baris) => {
                                        const rate = rates[k.id] ?? '';
                                        const galat = errors[String(k.id)];

                                        return (
                                            <tr
                                                key={k.id}
                                                className={cn(
                                                    'transition-colors hover:bg-muted/30',
                                                    dipilih.has(k.id) &&
                                                        'bg-primary/5',
                                                    galat &&
                                                        'bg-destructive/10',
                                                )}
                                            >
                                                <td className="px-3 py-1.5">
                                                    <Checkbox
                                                        aria-label={`Pilih ${k.karyawan?.nama}`}
                                                        checked={dipilih.has(
                                                            k.id,
                                                        )}
                                                        onCheckedChange={(v) =>
                                                            toggle(
                                                                k.id,
                                                                v === true,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-3 py-1.5">
                                                    <Karyawan kompensasi={k} />
                                                </td>
                                                <td className="px-3 py-1.5">
                                                    <Periode kompensasi={k} />
                                                </td>
                                                <td className="px-3 py-1.5 text-center tabular-nums">
                                                    {k.jumlah_hari}
                                                </td>
                                                <td className="px-3 py-1.5">
                                                    <Input
                                                        data-rate={baris}
                                                        type="number"
                                                        min={0}
                                                        placeholder="Rp"
                                                        aria-label={`Rate per hari ${k.karyawan?.nama}`}
                                                        aria-invalid={!!galat}
                                                        title={galat}
                                                        className={cn(
                                                            'h-8 tabular-nums',
                                                            rate !== '' &&
                                                                'border-amber-400 font-semibold dark:border-amber-700',
                                                        )}
                                                        value={rate}
                                                        onChange={(e) =>
                                                            setRates(
                                                                (prev) => ({
                                                                    ...prev,
                                                                    [k.id]:
                                                                        e.target
                                                                            .value,
                                                                }),
                                                            )
                                                        }
                                                        onKeyDown={(e) =>
                                                            pindahBaris(
                                                                e,
                                                                baris,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-3 py-1.5 text-right tabular-nums">
                                                    {rateValid(rate) ? (
                                                        formatRupiah(
                                                            Number(rate) *
                                                                k.jumlah_hari,
                                                        )
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                {!tabMenunggu &&
                                    rows.map((k) => (
                                        <tr
                                            key={k.id}
                                            className="transition-colors hover:bg-muted/30"
                                        >
                                            <td className="px-3 py-2">
                                                <Karyawan kompensasi={k} />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Periode kompensasi={k} />
                                            </td>
                                            <td className="px-3 py-2 text-center tabular-nums">
                                                {k.jumlah_hari}
                                            </td>
                                            <td className="px-3 py-2 tabular-nums">
                                                {k.rate_per_hari &&
                                                    formatRupiah(
                                                        k.rate_per_hari,
                                                    )}
                                            </td>
                                            <td className="px-3 py-2 text-right font-medium tabular-nums">
                                                {k.total_rupiah &&
                                                    formatRupiah(
                                                        k.total_rupiah,
                                                    )}
                                            </td>
                                            <td className="px-3 py-2 text-xs text-muted-foreground">
                                                <div>
                                                    {k.diproses_oleh?.nama}
                                                    {k.diproses_pada &&
                                                        ` · ${formatDateTime(k.diproses_pada)}`}
                                                </div>
                                                {k.catatan && (
                                                    <div
                                                        className="max-w-56 truncate"
                                                        title={k.catatan}
                                                    >
                                                        {k.catatan}
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Pagination
                    links={kompensasiCutis.links}
                    perPage={kompensasiCutis.per_page}
                />

                {tabMenunggu && siapDiproses.length > 0 && (
                    <div className="sticky bottom-4 z-10 flex flex-wrap items-center gap-3 rounded-lg border border-amber-300 bg-background/95 p-3 shadow-lg backdrop-blur dark:border-amber-800">
                        <div className="text-sm">
                            <span className="font-medium">
                                {siapDiproses.length} siap diproses
                            </span>
                            <span className="text-muted-foreground">
                                {' '}
                                · Total{' '}
                            </span>
                            <span className="font-semibold tabular-nums">
                                {formatRupiah(totalRupiah)}
                            </span>
                        </div>
                        <div className="min-w-56 flex-1">
                            <Input
                                placeholder="Catatan (opsional), mis. Dibayarkan bersama gaji Oktober"
                                value={catatan}
                                onChange={(e) => setCatatan(e.target.value)}
                            />
                            <InputError
                                message={
                                    errors.catatan ??
                                    errors.items ??
                                    (Object.keys(errors).length > 0
                                        ? 'Ada rate yang ditolak — baris ditandai merah.'
                                        : undefined)
                                }
                            />
                        </div>
                        <Button
                            variant="outline"
                            disabled={processing}
                            onClick={() => setRates({})}
                        >
                            Kosongkan
                        </Button>
                        <Button disabled={processing} onClick={proses}>
                            Proses {siapDiproses.length} kompensasi
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

KompensasiCutiIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Kompensasi Cuti', href: kompensasiIndex() },
    ],
};
