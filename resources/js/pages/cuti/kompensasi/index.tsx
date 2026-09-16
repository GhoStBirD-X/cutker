import { Head, router, usePage } from '@inertiajs/react';
import { FileClock } from 'lucide-react';
import { useState } from 'react';
import KompensasiCutiController from '@/actions/App/Http/Controllers/Cuti/KompensasiCutiController';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as kompensasiIndex } from '@/routes/cuti/kompensasi';
import type { KompensasiCuti, Paginated } from '@/types';

type PageProps = {
    kompensasiCutis: Paginated<KompensasiCuti>;
};

function formatRupiah(value: string): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function KompensasiRow({
    kompensasi,
    checked,
    onCheckedChange,
}: {
    kompensasi: KompensasiCuti;
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
}) {
    const menunggu = kompensasi.status === 'menunggu_diproses';

    return (
        <div
            className={cn(
                'flex flex-col gap-3 border-l-4 p-4 text-sm md:flex-row md:items-center md:justify-between',
                menunggu
                    ? 'border-l-amber-500 bg-amber-50/40 dark:bg-amber-950/10'
                    : 'border-l-transparent',
            )}
        >
            <div className="flex items-center gap-3">
                {menunggu && (
                    <Checkbox
                        checked={checked}
                        onCheckedChange={(value) =>
                            onCheckedChange(Boolean(value))
                        }
                    />
                )}
                <div>
                    <div className="font-medium">
                        {kompensasi.karyawan?.nama} &middot;{' '}
                        {kompensasi.jenis_cuti?.nama_jenis}
                    </div>
                    <div className="text-muted-foreground">
                        {kompensasi.jumlah_hari} hari sisa cuti hangus
                    </div>
                </div>
            </div>
            {!menunggu && (
                <div className="text-right">
                    <Badge variant="secondary">
                        {formatRupiah(kompensasi.total_rupiah!)}
                    </Badge>
                    <div className="mt-1 text-xs text-muted-foreground">
                        Diproses {kompensasi.diproses_oleh?.nama} &middot;{' '}
                        {kompensasi.diproses_pada &&
                            formatDateTime(kompensasi.diproses_pada)}
                    </div>
                </div>
            )}
        </div>
    );
}

export default function KompensasiCutiIndex() {
    const { kompensasiCutis } = usePage<PageProps>().props;

    const pending = kompensasiCutis.data.filter(
        (k) => k.status === 'menunggu_diproses',
    );

    const [selected, setSelected] = useState<Record<number, boolean>>({});
    const [ratePerHari, setRatePerHari] = useState('');
    const [catatan, setCatatan] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const jumlahDipilih = pending.filter((k) => selected[k.id]).length;
    const semuaTerpilih =
        pending.length > 0 && jumlahDipilih === pending.length;

    const toggleSemua = (checked: boolean) => {
        setSelected((prev) => {
            const next = { ...prev };
            pending.forEach((k) => {
                next[k.id] = checked;
            });

            return next;
        });
    };

    const submit = () => {
        const ids = pending.filter((k) => selected[k.id]).map((k) => k.id);

        setProcessing(true);
        router.post(
            KompensasiCutiController.prosesMassal.url(),
            {
                kompensasi_cuti_ids: ids,
                rate_per_hari: ratePerHari,
                catatan: catatan || undefined,
            },
            {
                onError: (err) => setErrors(err),
                onSuccess: () => {
                    setSelected({});
                    setRatePerHari('');
                    setCatatan('');
                    setErrors({});
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Kompensasi Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <FileClock className="size-5 text-amber-600 dark:text-amber-400" />
                    Kompensasi Cuti
                </h1>
                <p className="text-sm text-muted-foreground">
                    Sisa cuti tahunan/besar yang hangus saat periode ditutup
                    tercatat di sini. Pilih karyawan yang ratenya sama, isi satu
                    rate per hari, lalu proses sekaligus.
                </p>

                {pending.length > 0 && (
                    <Card>
                        <CardContent className="space-y-4">
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    checked={semuaTerpilih}
                                    onCheckedChange={(checked) =>
                                        toggleSemua(Boolean(checked))
                                    }
                                />
                                <span className="text-sm font-medium">
                                    Pilih semua yang menunggu di halaman ini (
                                    {pending.length})
                                </span>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="rate_per_hari">
                                        Rate per Hari (Rp)
                                    </Label>
                                    <Input
                                        id="rate_per_hari"
                                        type="number"
                                        min={0}
                                        placeholder="mis. 150000"
                                        value={ratePerHari}
                                        onChange={(e) =>
                                            setRatePerHari(e.target.value)
                                        }
                                    />
                                    <InputError
                                        message={errors.rate_per_hari}
                                    />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="catatan">
                                        Catatan (opsional)
                                    </Label>
                                    <Input
                                        id="catatan"
                                        placeholder="mis. Dibayarkan bersama gaji September"
                                        value={catatan}
                                        onChange={(e) =>
                                            setCatatan(e.target.value)
                                        }
                                    />
                                    <InputError message={errors.catatan} />
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <Button
                                    disabled={
                                        processing ||
                                        jumlahDipilih === 0 ||
                                        !ratePerHari
                                    }
                                    onClick={submit}
                                >
                                    Proses {jumlahDipilih} Terpilih
                                </Button>
                                <InputError
                                    message={errors.kompensasi_cuti_ids}
                                />
                            </div>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardContent className="divide-y p-0">
                        {kompensasiCutis.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Belum ada kompensasi cuti.
                            </p>
                        )}
                        {kompensasiCutis.data.map((kompensasi) => (
                            <KompensasiRow
                                key={kompensasi.id}
                                kompensasi={kompensasi}
                                checked={!!selected[kompensasi.id]}
                                onCheckedChange={(checked) =>
                                    setSelected((prev) => ({
                                        ...prev,
                                        [kompensasi.id]: checked,
                                    }))
                                }
                            />
                        ))}
                    </CardContent>
                </Card>

                <Pagination
                    links={kompensasiCutis.links}
                    perPage={kompensasiCutis.per_page}
                />
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
