import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import SaldoCutiController from '@/actions/App/Http/Controllers/Master/SaldoCutiController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { formatDate } from '@/lib/format';
import type { Departemen, JenisCuti, Karyawan } from '@/types';

type KaryawanOption = Pick<Karyawan, 'id' | 'nama' | 'nip' | 'departemen_id'>;

type PeriodeOption = {
    periode_ke: number;
    periode_mulai: string;
    periode_selesai: string;
};

type Props = {
    karyawans: KaryawanOption[];
    jenisCutis: JenisCuti[];
    departemens: Pick<Departemen, 'id' | 'nama_departemen'>[];
};

const emptyForm = {
    karyawan_ids: [] as number[],
    jenis_cuti_id: '',
    tahun: String(new Date().getFullYear()),
    periode_ke: '',
    kuota: '',
    terpakai: '0',
    catatan: '',
};

/**
 * Tambah saldo satu jenis cuti untuk satu atau banyak karyawan sekaligus.
 * Banyak karyawan → periode/tahun berjalan masing-masing (dihitung server).
 * Tepat satu karyawan + jenis cuti bertipe periode → HRD boleh memilih
 * nomor periode tertentu (mis. migrasi periode lampau), seperti form lama.
 */
export function SaldoCutiTambahDialog({
    karyawans,
    jenisCutis,
    departemens,
}: Props) {
    const [open, setOpen] = useState(false);
    const [cari, setCari] = useState('');
    const [departemenFilter, setDepartemenFilter] = useState('');
    const [hasilPeriode, setHasilPeriode] = useState<{
        kunci: string;
        periodes: PeriodeOption[];
    } | null>(null);

    const form = useForm(emptyForm);
    const { data, setData, processing } = form;
    const errors = form.errors as Record<string, string | undefined>;

    const jenisCutiTerpilih = jenisCutis.find(
        (j) => String(j.id) === data.jenis_cuti_id,
    );
    const bertipePeriode = jenisCutiTerpilih?.masa_kerja_minimal_bulan != null;
    const pilihPeriodeManual = bertipePeriode && data.karyawan_ids.length === 1;

    const karyawanTersaring = useMemo(() => {
        const kata = cari.trim().toLowerCase();

        return karyawans.filter(
            (k) =>
                (departemenFilter === '' ||
                    String(k.departemen_id) === departemenFilter) &&
                (kata === '' ||
                    k.nama.toLowerCase().includes(kata) ||
                    k.nip.toLowerCase().includes(kata)),
        );
    }, [karyawans, cari, departemenFilter]);

    const terpilih = new Set(data.karyawan_ids);
    const semuaTersaringTerpilih =
        karyawanTersaring.length > 0 &&
        karyawanTersaring.every((k) => terpilih.has(k.id));

    const toggleKaryawan = (id: number, dipilih: boolean) => {
        setData(
            'karyawan_ids',
            dipilih
                ? [...data.karyawan_ids, id]
                : data.karyawan_ids.filter((k) => k !== id),
        );
    };

    const toggleSemuaTersaring = (dipilih: boolean) => {
        const idTersaring = new Set(karyawanTersaring.map((k) => k.id));
        const sisa = data.karyawan_ids.filter((id) => !idTersaring.has(id));

        setData('karyawan_ids', dipilih ? [...sisa, ...idTersaring] : sisa);
    };

    const pilihJenisCuti = (id: string) => {
        const jenis = jenisCutis.find((j) => String(j.id) === id);

        setData((prev) => ({
            ...prev,
            jenis_cuti_id: id,
            kuota:
                jenis?.kuota_default == null ? '' : String(jenis.kuota_default),
        }));
    };

    // Periode selalu dihitung dari tanggal_masuk di backend supaya HRD
    // tidak bisa salah ketik tanggal mulai/selesai periode.
    const kunciPeriode = pilihPeriodeManual
        ? `${data.karyawan_ids[0]}:${data.jenis_cuti_id}`
        : null;
    const periodeOptions =
        kunciPeriode !== null && hasilPeriode?.kunci === kunciPeriode
            ? hasilPeriode.periodes
            : [];
    const loadingPeriode =
        kunciPeriode !== null && hasilPeriode?.kunci !== kunciPeriode;
    const periodeTerpilih = periodeOptions.find(
        (p) => String(p.periode_ke) === data.periode_ke,
    );

    useEffect(() => {
        if (kunciPeriode === null) {
            return;
        }

        let dibatalkan = false;

        fetch(
            SaldoCutiController.periodeTersedia.url({
                query: {
                    karyawan_id: data.karyawan_ids[0],
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

                setHasilPeriode({
                    kunci: kunciPeriode,
                    periodes: body.periodes,
                });
                setData(
                    'periode_ke',
                    body.periodes.length > 0
                        ? String(
                              body.periodes[body.periodes.length - 1]
                                  .periode_ke,
                          )
                        : '',
                );
            })
            .catch(() => {
                if (!dibatalkan) {
                    setHasilPeriode({ kunci: kunciPeriode, periodes: [] });
                }
            });

        return () => {
            dibatalkan = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [kunciPeriode]);

    const tutup = (nilai: boolean) => {
        setOpen(nilai);

        if (!nilai) {
            form.reset();
            form.clearErrors();
            setCari('');
            setDepartemenFilter('');
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const opsi = { preserveScroll: true, onSuccess: () => tutup(false) };

        if (pilihPeriodeManual) {
            form.transform((d) => ({
                karyawan_id: d.karyawan_ids[0],
                jenis_cuti_id: d.jenis_cuti_id,
                periode_ke: d.periode_ke,
                kuota: d.kuota,
                terpakai: d.terpakai,
                sisa:
                    d.kuota === ''
                        ? ''
                        : String(Number(d.kuota) - Number(d.terpakai)),
                catatan: d.catatan,
            }));
            form.post(SaldoCutiController.store.url(), opsi);

            return;
        }

        form.transform((d) => d);
        form.post(SaldoCutiController.storeMassal.url(), opsi);
    };

    return (
        <Dialog open={open} onOpenChange={tutup}>
            <DialogTrigger asChild>
                <Button className="gap-1.5">
                    <Plus className="size-4" />
                    Tambah Saldo
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogTitle>Tambah Saldo Cuti</DialogTitle>
                <DialogDescription>
                    Pilih satu atau banyak karyawan, lalu isi jenis cuti dan
                    angkanya sekali untuk semua. Karyawan yang sudah punya saldo
                    aktif untuk jenis cuti tersebut otomatis dilewati.
                </DialogDescription>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <div className="flex items-center justify-between">
                            <Label>Karyawan</Label>
                            <span className="text-xs text-muted-foreground">
                                {data.karyawan_ids.length} dipilih
                            </span>
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Input
                                placeholder="Cari nama/NIP..."
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                            />
                            <NativeSelect
                                wrapperClassName="sm:max-w-56"
                                value={departemenFilter}
                                onChange={(e) =>
                                    setDepartemenFilter(e.target.value)
                                }
                            >
                                <option value="">Semua departemen</option>
                                {departemens.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.nama_departemen}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="overflow-hidden rounded-md border">
                            <label className="flex cursor-pointer items-center gap-2 border-b bg-muted/40 px-3 py-2 text-xs font-medium text-muted-foreground">
                                <Checkbox
                                    checked={semuaTersaringTerpilih}
                                    onCheckedChange={(v) =>
                                        toggleSemuaTersaring(v === true)
                                    }
                                />
                                Pilih semua yang tampil (
                                {karyawanTersaring.length})
                            </label>
                            <div className="max-h-56 divide-y overflow-y-auto">
                                {karyawanTersaring.length === 0 && (
                                    <p className="px-3 py-2 text-sm text-muted-foreground">
                                        Tidak ada karyawan yang cocok.
                                    </p>
                                )}
                                {karyawanTersaring.map((k) => (
                                    <label
                                        key={k.id}
                                        className="flex cursor-pointer items-center gap-2 px-3 py-1.5 text-sm hover:bg-muted/30"
                                    >
                                        <Checkbox
                                            checked={terpilih.has(k.id)}
                                            onCheckedChange={(v) =>
                                                toggleKaryawan(k.id, v === true)
                                            }
                                        />
                                        <span className="font-medium">
                                            {k.nama}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {k.nip}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </div>
                        <InputError
                            message={errors.karyawan_ids ?? errors.karyawan_id}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="tambah_jenis_cuti_id">
                                Jenis Cuti
                            </Label>
                            <NativeSelect
                                id="tambah_jenis_cuti_id"
                                value={data.jenis_cuti_id}
                                onChange={(e) => pilihJenisCuti(e.target.value)}
                            >
                                <option value="">Pilih jenis cuti</option>
                                {jenisCutis.map((j) => (
                                    <option key={j.id} value={j.id}>
                                        {j.nama_jenis}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.jenis_cuti_id} />
                        </div>

                        {!bertipePeriode && (
                            <div className="grid gap-2">
                                <Label htmlFor="tambah_tahun">Tahun</Label>
                                <Input
                                    id="tambah_tahun"
                                    type="number"
                                    value={data.tahun}
                                    onChange={(e) =>
                                        setData('tahun', e.target.value)
                                    }
                                />
                                <InputError message={errors.tahun} />
                            </div>
                        )}

                        {bertipePeriode && !pilihPeriodeManual && (
                            <div className="grid gap-2">
                                <Label>Periode</Label>
                                <p className="flex min-h-9 items-center rounded-md border border-dashed border-input px-2 text-sm text-muted-foreground">
                                    Periode berjalan masing-masing karyawan
                                    (dihitung dari tanggal masuk)
                                </p>
                            </div>
                        )}

                        {pilihPeriodeManual && (
                            <div className="grid gap-2">
                                <Label htmlFor="tambah_periode_ke">
                                    Periode Ke-
                                </Label>
                                <NativeSelect
                                    id="tambah_periode_ke"
                                    value={data.periode_ke}
                                    disabled={
                                        loadingPeriode ||
                                        periodeOptions.length === 0
                                    }
                                    onChange={(e) =>
                                        setData('periode_ke', e.target.value)
                                    }
                                >
                                    {periodeOptions.length === 0 && (
                                        <option value="">
                                            {loadingPeriode
                                                ? 'Memuat periode...'
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
                                </NativeSelect>
                                <p className="text-xs text-muted-foreground">
                                    {periodeTerpilih
                                        ? `${formatDate(periodeTerpilih.periode_mulai)} s/d ${formatDate(periodeTerpilih.periode_selesai)}`
                                        : 'Dihitung dari tanggal masuk karyawan'}
                                </p>
                                <InputError message={errors.periode_ke} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="tambah_kuota">
                                Kuota (kosong = tanpa batas)
                            </Label>
                            <Input
                                id="tambah_kuota"
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
                            <Label htmlFor="tambah_terpakai">Terpakai</Label>
                            <Input
                                id="tambah_terpakai"
                                type="number"
                                min={0}
                                value={data.terpakai}
                                onChange={(e) =>
                                    setData('terpakai', e.target.value)
                                }
                            />
                            <p className="text-xs text-muted-foreground">
                                Sisa ={' '}
                                {data.kuota === ''
                                    ? 'tanpa batas'
                                    : `${Number(data.kuota) - Number(data.terpakai || 0)} hari`}
                            </p>
                            <InputError
                                message={errors.terpakai ?? errors.sisa}
                            />
                        </div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="tambah_catatan">
                                Catatan (opsional)
                            </Label>
                            <Input
                                id="tambah_catatan"
                                value={data.catatan}
                                onChange={(e) =>
                                    setData('catatan', e.target.value)
                                }
                            />
                            <InputError message={errors.catatan} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => tutup(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                processing ||
                                data.karyawan_ids.length === 0 ||
                                !data.jenis_cuti_id ||
                                (pilihPeriodeManual && !data.periode_ke)
                            }
                        >
                            Tambah untuk {data.karyawan_ids.length} karyawan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
