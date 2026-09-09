import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import KonfirmasiKontrakController from '@/actions/App/Http/Controllers/Cuti/KonfirmasiKontrakController';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as konfirmasiKontrakIndex } from '@/routes/cuti/konfirmasi-kontrak';
import type { KonfirmasiKontrakCuti, Paginated } from '@/types';

type PageProps = {
    konfirmasiKontraks: Paginated<KonfirmasiKontrakCuti>;
};

const STATUS_LABEL: Record<KonfirmasiKontrakCuti['status'], string> = {
    menunggu: 'Menunggu Konfirmasi',
    diperpanjang: 'Diperpanjang',
    tidak_diperpanjang: 'Tidak Diperpanjang',
};

function KonfirmasiRow({ konfirmasi }: { konfirmasi: KonfirmasiKontrakCuti }) {
    const [catatan, setCatatan] = useState('');
    const [processing, setProcessing] = useState(false);

    const submit = (diperpanjang: boolean) => {
        setProcessing(true);
        router.post(
            KonfirmasiKontrakController.konfirmasi.url(konfirmasi.id),
            { diperpanjang, catatan },
            { onFinish: () => setProcessing(false) },
        );
    };

    return (
        <div className="flex flex-col gap-3 p-4 text-sm md:flex-row md:items-center md:justify-between">
            <div>
                <div className="font-medium">
                    {konfirmasi.karyawan?.nama} &middot;{' '}
                    {konfirmasi.saldo_cuti?.jenis_cuti?.nama_jenis}
                </div>
                <div className="text-muted-foreground">
                    Periode ke-{konfirmasi.periode_ke} berakhir{' '}
                    {formatDate(konfirmasi.tanggal_batas)}
                </div>
            </div>
            {konfirmasi.status === 'menunggu' ? (
                <div className="flex flex-col gap-2 md:flex-row md:items-center">
                    <Input
                        placeholder="Catatan (opsional)"
                        value={catatan}
                        onChange={(e) => setCatatan(e.target.value)}
                        className="md:w-64"
                    />
                    <div className="flex gap-2">
                        <Button
                            size="sm"
                            disabled={processing}
                            onClick={() => submit(true)}
                        >
                            Perpanjang
                        </Button>
                        <Button
                            size="sm"
                            variant="destructive"
                            disabled={processing}
                            onClick={() => submit(false)}
                        >
                            Tidak Diperpanjang
                        </Button>
                    </div>
                </div>
            ) : (
                <Badge
                    variant={
                        konfirmasi.status === 'diperpanjang'
                            ? 'secondary'
                            : 'destructive'
                    }
                >
                    {STATUS_LABEL[konfirmasi.status]}
                </Badge>
            )}
        </div>
    );
}

export default function KonfirmasiKontrakIndex() {
    const { konfirmasiKontraks } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Konfirmasi Perpanjangan Kontrak" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">
                    Konfirmasi Perpanjangan Kontrak
                </h1>
                <p className="text-sm text-muted-foreground">
                    Karyawan kontrak yang periode cuti tahunannya sudah berakhir
                    tertahan di sini sampai HRD mengonfirmasi status
                    perpanjangan kontraknya.
                </p>

                <Card>
                    <CardContent className="divide-y p-0">
                        {konfirmasiKontraks.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Tidak ada konfirmasi yang perlu ditindak.
                            </p>
                        )}
                        {konfirmasiKontraks.data.map((konfirmasi) => (
                            <KonfirmasiRow
                                key={konfirmasi.id}
                                konfirmasi={konfirmasi}
                            />
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={konfirmasiKontraks.links} />
            </div>
        </>
    );
}

KonfirmasiKontrakIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        {
            title: 'Konfirmasi Kontrak',
            href: konfirmasiKontrakIndex(),
        },
    ],
};
