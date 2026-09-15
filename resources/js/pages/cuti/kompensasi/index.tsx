import { Head, router, usePage } from '@inertiajs/react';
import { FileClock } from 'lucide-react';
import { useState } from 'react';
import KompensasiCutiController from '@/actions/App/Http/Controllers/Cuti/KompensasiCutiController';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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

function KompensasiRow({ kompensasi }: { kompensasi: KompensasiCuti }) {
    const [ratePerHari, setRatePerHari] = useState('');
    const [processing, setProcessing] = useState(false);

    const submit = () => {
        setProcessing(true);
        router.post(
            KompensasiCutiController.proses.url(kompensasi.id),
            { rate_per_hari: ratePerHari },
            { onFinish: () => setProcessing(false) },
        );
    };

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
            <div>
                <div className="font-medium">
                    {kompensasi.karyawan?.nama} &middot;{' '}
                    {kompensasi.jenis_cuti?.nama_jenis}
                </div>
                <div className="text-muted-foreground">
                    {kompensasi.jumlah_hari} hari sisa cuti hangus
                </div>
            </div>
            {kompensasi.status === 'menunggu_diproses' ? (
                <div className="flex items-center gap-2">
                    <Input
                        type="number"
                        min={0}
                        placeholder="Rate per hari (Rp)"
                        value={ratePerHari}
                        onChange={(e) => setRatePerHari(e.target.value)}
                        className="md:w-48"
                    />
                    <Button size="sm" disabled={processing} onClick={submit}>
                        Proses
                    </Button>
                </div>
            ) : (
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
                    tercatat di sini. Masukkan rate per hari untuk menghitung
                    nilai yang harus dibayarkan ke karyawan.
                </p>

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
                            />
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={kompensasiCutis.links} />
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
