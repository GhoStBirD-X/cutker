import { router } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import SaldoCutiController from '@/actions/App/Http/Controllers/Master/SaldoCutiController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

export type AksiMassalOption = {
    value: string;
    label: string;
    butuh_nilai: boolean;
};

export type SaldoCutiFilters = {
    search: string;
    jenis_cuti_id: number | null;
    departemen_id: number | null;
    tahun: number | null;
};

type NilaiSaldo = {
    kuota: number | null;
    terpakai: number;
    sisa: number | null;
};

type BarisPratinjau = {
    id: number;
    nama: string;
    nip: string;
    jenis_cuti: string;
    periode: string;
    sebelum: NilaiSaldo;
    sesudah: NilaiSaldo;
    galat: string | null;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Daftar id yang dicentang, atau `null` = semua baris sesuai filter. */
    ids: number[] | null;
    jumlah: number;
    filters: SaldoCutiFilters;
    aksiOptions: AksiMassalOption[];
    onSelesai: () => void;
};

const tampil = (nilai: number | null) => (nilai === null ? '∞' : nilai);

/**
 * Aksi seragam ke banyak baris saldo sekaligus (set/tambah/kurangi kuota,
 * hapus). Hasil sebelum → sesudah selalu dihitung server (endpoint
 * pratinjau) dengan logika yang sama persis dengan saat diterapkan.
 */
export function SaldoCutiAksiMassalDialog({
    open,
    onOpenChange,
    ids,
    jumlah,
    filters,
    aksiOptions,
    onSelesai,
}: Props) {
    const [aksi, setAksi] = useState(aksiOptions[0]?.value ?? '');
    const [nilai, setNilai] = useState('');
    const [catatan, setCatatan] = useState('');
    const [pratinjau, setPratinjau] = useState<BarisPratinjau[] | null>(null);
    const [memuat, setMemuat] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const aksiTerpilih = aksiOptions.find((a) => a.value === aksi);
    const butuhNilai = aksiTerpilih?.butuh_nilai ?? false;
    const siapDipratinjau = aksi !== '' && (!butuhNilai || nilai !== '');

    const target = {
        aksi,
        nilai: butuhNilai ? nilai : '',
        semua: ids === null ? '1' : '0',
        ...(ids === null
            ? {
                  search: filters.search,
                  jenis_cuti_id: filters.jenis_cuti_id ?? '',
                  departemen_id: filters.departemen_id ?? '',
                  tahun: filters.tahun ?? '',
              }
            : { ids }),
    };

    useEffect(() => {
        if (!open || !siapDipratinjau) {
            return;
        }

        let dibatalkan = false;
        const timer = window.setTimeout(() => {
            setMemuat(true);
            fetch(
                SaldoCutiController.pratinjauAksiMassal.url({ query: target }),
                {
                    headers: { Accept: 'application/json' },
                },
            )
                .then((response) => response.json())
                .then((body: { baris?: BarisPratinjau[] }) => {
                    if (!dibatalkan) {
                        setPratinjau(body.baris ?? []);
                    }
                })
                .catch(() => {
                    if (!dibatalkan) {
                        setPratinjau([]);
                    }
                })
                .finally(() => {
                    if (!dibatalkan) {
                        setMemuat(false);
                    }
                });
        }, 300);

        return () => {
            dibatalkan = true;
            window.clearTimeout(timer);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        open,
        aksi,
        nilai,
        siapDipratinjau,
        JSON.stringify(ids),
        JSON.stringify(filters),
    ]);

    const tutup = (nilaiOpen: boolean) => {
        onOpenChange(nilaiOpen);

        if (!nilaiOpen) {
            setNilai('');
            setCatatan('');
            setPratinjau(null);
            setErrors({});
        }
    };

    const valid = pratinjau?.filter((b) => b.galat === null) ?? [];
    const dilewati = (pratinjau?.length ?? 0) - valid.length;
    const hapus = aksi === 'hapus';

    const terapkan = (e: React.FormEvent) => {
        e.preventDefault();

        router.post(
            SaldoCutiController.aksiMassal.url(),
            { ...target, catatan },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (e) => setErrors(e),
                onSuccess: () => {
                    tutup(false);
                    onSelesai();
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={tutup}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogTitle>Aksi Massal</DialogTitle>
                <DialogDescription>
                    Diterapkan ke {jumlah} baris saldo
                    {ids === null ? ' (semua hasil filter)' : ' yang dipilih'}.
                    Baris yang hasilnya tidak valid otomatis dilewati.
                </DialogDescription>

                <form onSubmit={terapkan} className="grid gap-4">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="aksi">Aksi</Label>
                            <NativeSelect
                                id="aksi"
                                value={aksi}
                                onChange={(e) => setAksi(e.target.value)}
                            >
                                {aksiOptions.map((a) => (
                                    <option key={a.value} value={a.value}>
                                        {a.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.aksi} />
                        </div>
                        {butuhNilai && (
                            <div className="grid gap-2">
                                <Label htmlFor="nilai">Jumlah hari</Label>
                                <Input
                                    id="nilai"
                                    type="number"
                                    min={0}
                                    autoFocus
                                    value={nilai}
                                    onChange={(e) => setNilai(e.target.value)}
                                />
                                <InputError message={errors.nilai} />
                            </div>
                        )}
                    </div>

                    <div className="overflow-hidden rounded-md border">
                        <div className="flex items-center justify-between gap-2 border-b bg-muted/40 px-3 py-2 text-xs">
                            <span className="font-medium text-muted-foreground">
                                Pratinjau
                            </span>
                            {memuat && <Spinner className="size-3.5" />}
                            {!memuat && pratinjau && (
                                <span className="flex gap-1.5">
                                    <Badge variant="secondary">
                                        {valid.length}{' '}
                                        {hapus ? 'dihapus' : 'diubah'}
                                    </Badge>
                                    {dilewati > 0 && (
                                        <Badge
                                            variant="outline"
                                            className="border-amber-300 text-amber-700 dark:border-amber-800 dark:text-amber-400"
                                        >
                                            {dilewati} dilewati
                                        </Badge>
                                    )}
                                </span>
                            )}
                        </div>
                        <div className="max-h-72 overflow-y-auto">
                            {!siapDipratinjau && (
                                <p className="px-3 py-2 text-sm text-muted-foreground">
                                    Isi jumlah hari untuk melihat pratinjau.
                                </p>
                            )}
                            {siapDipratinjau && pratinjau && (
                                <table className="w-full text-left text-xs">
                                    <thead className="sticky top-0 bg-background">
                                        <tr className="border-b text-muted-foreground">
                                            <th className="px-3 py-1.5 font-medium">
                                                Karyawan
                                            </th>
                                            <th className="px-3 py-1.5 font-medium">
                                                Jenis
                                            </th>
                                            <th className="px-3 py-1.5 text-center font-medium">
                                                Kuota
                                            </th>
                                            <th className="px-3 py-1.5 text-center font-medium">
                                                Sisa
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {pratinjau.map((b) => (
                                            <tr
                                                key={b.id}
                                                className={cn(
                                                    b.galat &&
                                                        'bg-amber-50/60 text-muted-foreground dark:bg-amber-950/20',
                                                )}
                                            >
                                                <td className="px-3 py-1.5">
                                                    <div className="font-medium text-foreground">
                                                        {b.nama}
                                                    </div>
                                                    {b.galat && (
                                                        <div className="text-amber-700 dark:text-amber-400">
                                                            {b.galat}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-3 py-1.5">
                                                    {b.jenis_cuti}
                                                    <div className="text-muted-foreground">
                                                        {b.periode}
                                                    </div>
                                                </td>
                                                {(
                                                    ['kuota', 'sisa'] as const
                                                ).map((kolom) => (
                                                    <td
                                                        key={kolom}
                                                        className="px-3 py-1.5 text-center whitespace-nowrap tabular-nums"
                                                    >
                                                        {hapus || b.galat ? (
                                                            tampil(
                                                                b.sebelum[
                                                                    kolom
                                                                ],
                                                            )
                                                        ) : (
                                                            <span className="inline-flex items-center gap-1">
                                                                <span className="text-muted-foreground">
                                                                    {tampil(
                                                                        b
                                                                            .sebelum[
                                                                            kolom
                                                                        ],
                                                                    )}
                                                                </span>
                                                                <ArrowRight className="size-3 text-muted-foreground" />
                                                                <span
                                                                    className={cn(
                                                                        'font-semibold',
                                                                        b
                                                                            .sesudah[
                                                                            kolom
                                                                        ] !==
                                                                            b
                                                                                .sebelum[
                                                                                kolom
                                                                            ] &&
                                                                            'text-primary',
                                                                    )}
                                                                >
                                                                    {tampil(
                                                                        b
                                                                            .sesudah[
                                                                            kolom
                                                                        ],
                                                                    )}
                                                                </span>
                                                            </span>
                                                        )}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="aksi_catatan">
                            Catatan (wajib, dicatat di setiap baris)
                        </Label>
                        <Input
                            id="aksi_catatan"
                            value={catatan}
                            onChange={(e) => setCatatan(e.target.value)}
                            placeholder="mis. Tambahan cuti kebijakan direksi 2026"
                        />
                        <InputError message={errors.catatan ?? errors.ids} />
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
                            variant={hapus ? 'destructive' : 'default'}
                            disabled={
                                processing ||
                                memuat ||
                                !siapDipratinjau ||
                                valid.length === 0 ||
                                catatan.trim() === ''
                            }
                        >
                            {hapus ? 'Hapus' : 'Terapkan ke'} {valid.length}{' '}
                            baris
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
