import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import CutiMassalController from '@/actions/App/Http/Controllers/Cuti/CutiMassalController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { saldoSeverity } from '@/lib/saldo-severity';
import { dashboard } from '@/routes';
import {
    create as cutiMassalCreate,
    index as cutiMassalIndex,
} from '@/routes/cuti/massal';
import type { JenisCuti, KaryawanEligiblePreview } from '@/types';

type PageProps = {
    jenisCutis: JenisCuti[];
    eligibleKaryawans: KaryawanEligiblePreview[] | null;
    filter: {
        jenis_cuti_id?: string;
        tanggal_mulai?: string;
        tanggal_selesai?: string;
    };
};

export default function CutiMassalCreate() {
    const { jenisCutis, eligibleKaryawans, filter } =
        usePage<PageProps>().props;
    const today = new Date().toISOString().slice(0, 10);

    const [jenisCutiId, setJenisCutiId] = useState(filter.jenis_cuti_id ?? '');
    const [tanggalMulai, setTanggalMulai] = useState(
        filter.tanggal_mulai ?? '',
    );
    const [tanggalSelesai, setTanggalSelesai] = useState(
        filter.tanggal_selesai ?? '',
    );
    const [alasan, setAlasan] = useState('');
    const [selected, setSelected] = useState<Record<number, boolean>>({});
    const [seededFor, setSeededFor] = useState<
        KaryawanEligiblePreview[] | null
    >(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    // Reset seleksi karyawan setiap kali daftar eligible yang dikirim
    // server berubah (mis. setelah klik "Muat Karyawan" dengan filter baru).
    if (eligibleKaryawans !== seededFor) {
        setSeededFor(eligibleKaryawans);

        const seeded: Record<number, boolean> = {};
        eligibleKaryawans?.forEach((row) => {
            seeded[row.karyawan.id] = true;
        });
        setSelected(seeded);
    }

    const kelompokDepartemen = useMemo(() => {
        if (!eligibleKaryawans) {
            return [];
        }

        const map = new Map<string, KaryawanEligiblePreview[]>();
        eligibleKaryawans.forEach((row) => {
            const nama =
                row.karyawan.departemen?.nama_departemen ?? 'Tanpa Departemen';
            map.set(nama, [...(map.get(nama) ?? []), row]);
        });

        return Array.from(map.entries());
    }, [eligibleKaryawans]);

    const jumlahDipilih = Object.values(selected).filter(Boolean).length;

    const muatKaryawan = () => {
        router.get(
            cutiMassalCreate.url(),
            {
                jenis_cuti_id: jenisCutiId,
                tanggal_mulai: tanggalMulai,
                tanggal_selesai: tanggalSelesai,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const toggleSemua = (checked: boolean, rows: KaryawanEligiblePreview[]) => {
        setSelected((prev) => {
            const next = { ...prev };
            rows.forEach((row) => {
                next[row.karyawan.id] = checked;
            });

            return next;
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const karyawanIds = Object.entries(selected)
            .filter(([, checked]) => checked)
            .map(([id]) => Number(id));

        setProcessing(true);
        router.post(
            CutiMassalController.store.url(),
            {
                jenis_cuti_id: jenisCutiId,
                tanggal_mulai: tanggalMulai,
                tanggal_selesai: tanggalSelesai,
                alasan,
                karyawan_ids: karyawanIds,
            },
            {
                onError: (err) => setErrors(err),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Buat Cuti Massal" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Buat Cuti Massal</h1>
                <p className="text-sm text-muted-foreground">
                    Tetapkan cuti untuk banyak karyawan sekaligus (mis. cuti
                    bersama). Karyawan yang saldonya belum cukup (biasanya
                    karyawan baru) tetap diproses dan saldonya boleh menjadi
                    minus.
                </p>

                <Card className="max-w-3xl">
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="jenis_cuti_id">
                                    Jenis Cuti
                                </Label>
                                <Select
                                    value={jenisCutiId}
                                    onValueChange={setJenisCutiId}
                                >
                                    <SelectTrigger
                                        id="jenis_cuti_id"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih jenis cuti" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {jenisCutis.map((jenis) => (
                                            <SelectItem
                                                key={jenis.id}
                                                value={String(jenis.id)}
                                            >
                                                {jenis.nama_jenis}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.jenis_cuti_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="filter_tanggal_mulai">
                                    Tanggal Mulai
                                </Label>
                                <Input
                                    id="filter_tanggal_mulai"
                                    type="date"
                                    min={today}
                                    value={tanggalMulai}
                                    onChange={(e) =>
                                        setTanggalMulai(e.target.value)
                                    }
                                />
                                <InputError message={errors.tanggal_mulai} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="filter_tanggal_selesai">
                                    Tanggal Selesai
                                </Label>
                                <Input
                                    id="filter_tanggal_selesai"
                                    type="date"
                                    min={tanggalMulai || today}
                                    value={tanggalSelesai}
                                    onChange={(e) =>
                                        setTanggalSelesai(e.target.value)
                                    }
                                />
                                <InputError message={errors.tanggal_selesai} />
                            </div>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            disabled={
                                !jenisCutiId || !tanggalMulai || !tanggalSelesai
                            }
                            onClick={muatKaryawan}
                        >
                            Muat Karyawan
                        </Button>
                    </CardContent>
                </Card>

                {eligibleKaryawans && (
                    <form onSubmit={submit} className="space-y-4">
                        <Card className="max-w-3xl">
                            <CardContent className="space-y-3 p-0">
                                {eligibleKaryawans.length === 0 && (
                                    <p className="p-4 text-sm text-muted-foreground">
                                        Tidak ada karyawan aktif yang eligible
                                        untuk jenis cuti ini.
                                    </p>
                                )}
                                {kelompokDepartemen.map(([nama, rows]) => {
                                    const semuaTerpilih = rows.every(
                                        (row) => selected[row.karyawan.id],
                                    );

                                    return (
                                        <div key={nama} className="divide-y">
                                            <div className="flex items-center justify-between gap-2 bg-muted/50 px-4 py-2">
                                                <div className="flex items-center gap-2">
                                                    <Checkbox
                                                        checked={semuaTerpilih}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            toggleSemua(
                                                                Boolean(
                                                                    checked,
                                                                ),
                                                                rows,
                                                            )
                                                        }
                                                    />
                                                    <span className="text-sm font-medium">
                                                        {nama}
                                                    </span>
                                                </div>
                                                <span className="text-xs text-muted-foreground">
                                                    {rows.length} karyawan
                                                </span>
                                            </div>
                                            {rows.map((row) => (
                                                <label
                                                    key={row.karyawan.id}
                                                    className="flex cursor-pointer items-center justify-between gap-3 px-4 py-2 text-sm transition-colors hover:bg-accent/50"
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <Checkbox
                                                            checked={
                                                                !!selected[
                                                                    row.karyawan
                                                                        .id
                                                                ]
                                                            }
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                setSelected(
                                                                    (prev) => ({
                                                                        ...prev,
                                                                        [row
                                                                            .karyawan
                                                                            .id]:
                                                                            Boolean(
                                                                                checked,
                                                                            ),
                                                                    }),
                                                                )
                                                            }
                                                        />
                                                        <span>
                                                            {row.karyawan.nama}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <span
                                                            className={
                                                                row.saldo
                                                                    ? saldoSeverity(
                                                                          row
                                                                              .saldo
                                                                              .sisa,
                                                                          row
                                                                              .saldo
                                                                              .kuota,
                                                                      ).text
                                                                    : 'text-muted-foreground'
                                                            }
                                                        >
                                                            {row.saldo
                                                                ? row.saldo
                                                                      .kuota ===
                                                                  null
                                                                    ? 'sisa tanpa batas'
                                                                    : `sisa ${row.saldo.sisa} hari`
                                                                : 'tidak ada saldo aktif'}
                                                        </span>
                                                        {row.akan_minus && (
                                                            <Badge variant="destructive">
                                                                Akan minus
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </label>
                                            ))}
                                        </div>
                                    );
                                })}
                            </CardContent>
                        </Card>

                        <Card className="max-w-3xl">
                            <CardContent className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    {jumlahDipilih} dari{' '}
                                    {eligibleKaryawans.length} karyawan
                                    terpilih.
                                </p>

                                <div className="grid gap-2">
                                    <Label htmlFor="alasan">Alasan</Label>
                                    <Textarea
                                        id="alasan"
                                        maxLength={255}
                                        required
                                        value={alasan}
                                        onChange={(e) =>
                                            setAlasan(e.target.value)
                                        }
                                    />
                                    <InputError message={errors.alasan} />
                                    <InputError message={errors.karyawan_ids} />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing || jumlahDipilih === 0}
                                >
                                    Buat Cuti Massal
                                </Button>
                            </CardContent>
                        </Card>
                    </form>
                )}
            </div>
        </>
    );
}

CutiMassalCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Cuti Massal', href: cutiMassalIndex() },
        { title: 'Buat Cuti Massal', href: cutiMassalCreate() },
    ],
};
