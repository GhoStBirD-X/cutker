import { router, useForm } from '@inertiajs/react';
import { ArrowRight, Download, Upload, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import SaldoCutiController from '@/actions/App/Http/Controllers/Master/SaldoCutiController';
import InputError from '@/components/input-error';
import type { SaldoCutiFilters } from '@/components/saldo-cuti-aksi-massal-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { cn } from '@/lib/utils';

type NilaiSaldo = {
    kuota: number | null;
    terpakai: number;
    sisa: number | null;
};

type ImportPratinjau = {
    perubahan: {
        id: number;
        baris: number;
        nip: string;
        nama: string;
        jenis_cuti: string;
        periode: string;
        sebelum: NilaiSaldo;
        sesudah: NilaiSaldo;
    }[];
    failures: { row: number; errors: string[] }[];
    tidak_berubah: number;
};

const KOLOM = ['kuota', 'terpakai', 'sisa'] as const;

const tampil = (nilai: number | null) => (nilai === null ? '∞' : nilai);

/**
 * Alur Excel 3 langkah: unduh data (sesuai filter) → edit kolom
 * kuota/terpakai/sisa → unggah. Unggahan hanya menghasilkan pratinjau;
 * baru tersimpan setelah HRD menekan "Terapkan" (lewat update massal).
 */
export function SaldoCutiImportDialog({
    filters,
}: {
    filters: SaldoCutiFilters;
}) {
    const [open, setOpen] = useState(false);
    const [pratinjau, setPratinjau] = useState<ImportPratinjau | null>(null);
    const [catatan, setCatatan] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [menyimpan, setMenyimpan] = useState(false);
    const uploadForm = useForm<{ file: File | null }>({ file: null });

    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash as
                { importPratinjau?: ImportPratinjau } | undefined;

            if (flash?.importPratinjau) {
                setPratinjau(flash.importPratinjau);
                setCatatan(
                    `Import Excel ${new Date().toLocaleDateString('id-ID')}`,
                );
            }
        });
    }, []);

    const tutup = (nilai: boolean) => {
        setOpen(nilai);

        if (!nilai) {
            setPratinjau(null);
            setErrors({});
            uploadForm.reset();
            uploadForm.clearErrors();
        }
    };

    const unggah = (e: React.FormEvent) => {
        e.preventDefault();

        if (!uploadForm.data.file) {
            return;
        }

        setPratinjau(null);
        uploadForm.post(SaldoCutiController.pratinjauImport.url(), {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
        });
    };

    const terapkan = () => {
        if (!pratinjau) {
            return;
        }

        router.put(
            SaldoCutiController.updateMassal.url(),
            {
                perubahan: pratinjau.perubahan.map((p) => ({
                    id: p.id,
                    ...p.sesudah,
                })),
                catatan,
            },
            {
                preserveScroll: true,
                onStart: () => setMenyimpan(true),
                onFinish: () => setMenyimpan(false),
                onError: (e) => setErrors(e),
                onSuccess: () => tutup(false),
            },
        );
    };

    const query = Object.fromEntries(
        Object.entries(filters).filter(([, v]) => v !== null && v !== ''),
    );

    return (
        <Dialog open={open} onOpenChange={tutup}>
            <DialogTrigger asChild>
                <Button variant="outline" className="gap-1.5">
                    <Upload className="size-4" />
                    Template & Import Excel
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogTitle>Edit Saldo lewat Excel</DialogTitle>
                <DialogDescription>
                    Untuk koreksi ratusan baris sekaligus. Hanya mengubah saldo
                    yang sudah ada — gunakan "Tambah Saldo" untuk membuat baris
                    baru.
                </DialogDescription>

                <ol className="grid gap-3 text-sm">
                    <li className="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">1</Badge>
                        <span>
                            Unduh template — sudah berisi semua saldo karyawan
                            sesuai filter halaman ini
                        </span>
                        <Button
                            asChild
                            size="sm"
                            variant="outline"
                            className="gap-1.5"
                        >
                            <a
                                href={SaldoCutiController.exportMethod.url({
                                    query,
                                })}
                            >
                                <Download className="size-4" />
                                Unduh Template
                            </a>
                        </Button>
                    </li>
                    <li className="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">2</Badge>
                        <span>
                            Isi kolom berwarna kuning: <b>kuota</b>,{' '}
                            <b>terpakai</b>, <b>sisa</b> (kosong = tanpa batas).
                            Kolom abu-abu jangan diubah, terutama kolom{' '}
                            <b>id</b> & <b>nip</b>.
                        </span>
                    </li>
                    <li>
                        <form
                            onSubmit={unggah}
                            className="flex flex-wrap items-center gap-2"
                        >
                            <Badge variant="secondary">3</Badge>
                            <Input
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                className="max-w-xs"
                                onChange={(e) =>
                                    uploadForm.setData(
                                        'file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <Button
                                type="submit"
                                size="sm"
                                disabled={
                                    uploadForm.processing ||
                                    !uploadForm.data.file
                                }
                            >
                                Cek File
                            </Button>
                            <InputError message={uploadForm.errors.file} />
                        </form>
                    </li>
                </ol>

                {pratinjau && (
                    <div className="grid gap-3">
                        <div className="flex flex-wrap gap-1.5 text-xs">
                            <Badge variant="secondary">
                                {pratinjau.perubahan.length} baris berubah
                            </Badge>
                            <Badge variant="outline">
                                {pratinjau.tidak_berubah} tidak berubah
                            </Badge>
                            {pratinjau.failures.length > 0 && (
                                <Badge
                                    variant="outline"
                                    className="border-destructive/40 text-destructive"
                                >
                                    {pratinjau.failures.length} baris bermasalah
                                </Badge>
                            )}
                        </div>

                        {pratinjau.failures.length > 0 && (
                            <div className="overflow-hidden rounded-md border border-destructive/50">
                                <div className="flex items-center gap-2 border-b border-destructive/30 bg-destructive/10 px-3 py-2">
                                    <XCircle className="size-4 shrink-0 text-destructive" />
                                    <h3 className="text-sm font-semibold text-destructive">
                                        Baris berikut tidak akan diproses
                                    </h3>
                                </div>
                                <ul className="max-h-40 divide-y overflow-y-auto text-xs">
                                    {pratinjau.failures.map((failure) => (
                                        <li
                                            key={failure.row}
                                            className="flex gap-2 px-3 py-2"
                                        >
                                            <Badge
                                                variant="outline"
                                                className="shrink-0 border-destructive/40 text-destructive"
                                            >
                                                Baris {failure.row}
                                            </Badge>
                                            <span className="text-muted-foreground">
                                                {failure.errors.join(' ')}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {pratinjau.perubahan.length > 0 && (
                            <div className="max-h-72 overflow-auto rounded-md border">
                                <table className="w-full text-left text-xs">
                                    <thead className="sticky top-0 bg-muted">
                                        <tr className="text-muted-foreground">
                                            <th className="px-3 py-1.5 font-medium">
                                                Karyawan
                                            </th>
                                            <th className="px-3 py-1.5 font-medium">
                                                Jenis
                                            </th>
                                            {KOLOM.map((k) => (
                                                <th
                                                    key={k}
                                                    className="px-3 py-1.5 text-center font-medium capitalize"
                                                >
                                                    {k}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {pratinjau.perubahan.map(
                                            (p, indeks) => (
                                                <tr
                                                    key={p.id}
                                                    className={cn(
                                                        Object.keys(
                                                            errors,
                                                        ).some((k) =>
                                                            k.startsWith(
                                                                `perubahan.${indeks}.`,
                                                            ),
                                                        ) &&
                                                            'bg-destructive/10',
                                                    )}
                                                >
                                                    <td className="px-3 py-1.5">
                                                        <div className="font-medium">
                                                            {p.nama}
                                                        </div>
                                                        <div className="text-muted-foreground">
                                                            {p.nip} · baris{' '}
                                                            {p.baris}
                                                        </div>
                                                    </td>
                                                    <td className="px-3 py-1.5">
                                                        {p.jenis_cuti}
                                                        <div className="text-muted-foreground">
                                                            {p.periode}
                                                        </div>
                                                    </td>
                                                    {KOLOM.map((k) => (
                                                        <td
                                                            key={k}
                                                            className="px-3 py-1.5 text-center whitespace-nowrap tabular-nums"
                                                        >
                                                            {p.sebelum[k] ===
                                                            p.sesudah[k] ? (
                                                                <span className="text-muted-foreground">
                                                                    {tampil(
                                                                        p
                                                                            .sesudah[
                                                                            k
                                                                        ],
                                                                    )}
                                                                </span>
                                                            ) : (
                                                                <span className="inline-flex items-center gap-1">
                                                                    <span className="text-muted-foreground line-through">
                                                                        {tampil(
                                                                            p
                                                                                .sebelum[
                                                                                k
                                                                            ],
                                                                        )}
                                                                    </span>
                                                                    <ArrowRight className="size-3 text-muted-foreground" />
                                                                    <span className="font-semibold text-primary">
                                                                        {tampil(
                                                                            p
                                                                                .sesudah[
                                                                                k
                                                                            ],
                                                                        )}
                                                                    </span>
                                                                </span>
                                                            )}
                                                        </td>
                                                    ))}
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {pratinjau.perubahan.length > 0 && (
                            <div className="grid gap-2">
                                <Label htmlFor="import_catatan">
                                    Catatan (wajib, dicatat di setiap baris)
                                </Label>
                                <Input
                                    id="import_catatan"
                                    value={catatan}
                                    onChange={(e) => setCatatan(e.target.value)}
                                />
                                <InputError
                                    message={
                                        errors.catatan ??
                                        (Object.keys(errors).length > 0
                                            ? 'Ada baris yang tidak valid (ditandai merah).'
                                            : undefined)
                                    }
                                />
                            </div>
                        )}
                    </div>
                )}

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => tutup(false)}
                    >
                        Tutup
                    </Button>
                    {pratinjau && pratinjau.perubahan.length > 0 && (
                        <Button
                            type="button"
                            onClick={terapkan}
                            disabled={menyimpan || catatan.trim() === ''}
                        >
                            Terapkan {pratinjau.perubahan.length} perubahan
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
