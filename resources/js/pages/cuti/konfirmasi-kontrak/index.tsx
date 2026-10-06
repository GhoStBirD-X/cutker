import { Head, router, usePage } from '@inertiajs/react';
import { CalendarClock, CalendarPlus, UserCheck } from 'lucide-react';
import { useState } from 'react';
import KonfirmasiKontrakController from '@/actions/App/Http/Controllers/Cuti/KonfirmasiKontrakController';
import { konfirmasi as tampilkanKonfirmasi } from '@/components/confirm-dialog';
import type { KonfirmasiOptions } from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as konfirmasiKontrakIndex } from '@/routes/cuti/konfirmasi-kontrak';
import type { KonfirmasiKontrakCuti, Paginated } from '@/types';

type PageProps = {
    konfirmasiKontraks: Paginated<KonfirmasiKontrakCuti>;
    jumlahMenunggu: number;
};

const STATUS_LABEL: Record<KonfirmasiKontrakCuti['status'], string> = {
    menunggu: 'Menunggu Konfirmasi',
    diperpanjang: 'Diperpanjang',
    tidak_diperpanjang: 'Tidak Diperpanjang',
    diangkat_tetap: 'Diangkat Karyawan Tetap',
};

/** Tanggal setelah "YYYY-MM-DD" yang diberikan, sesuai batas minimal validasi backend ("after:tanggal_batas"). */
function tanggalSetelah(tanggal: string): string {
    const [tahun, bulan, hari] = tanggal.split('-').map(Number);
    const tanggalBerikutnya = new Date(tahun, bulan - 1, hari + 1);

    return `${tanggalBerikutnya.getFullYear()}-${String(tanggalBerikutnya.getMonth() + 1).padStart(2, '0')}-${String(tanggalBerikutnya.getDate()).padStart(2, '0')}`;
}

/** Tanggal "YYYY-MM-DD" yang diberikan ditambah tepat 1 tahun, untuk isi cepat tombol "1 Tahun". */
function tambahSatuTahun(tanggal: string): string {
    const [tahun, bulan, hari] = tanggal.split('-').map(Number);

    return `${tahun + 1}-${String(bulan).padStart(2, '0')}-${String(hari).padStart(2, '0')}`;
}

type Keputusan = 'perpanjang' | 'angkat_tetap' | 'tidak_diperpanjang';

/** Urutan kontrak terakhir dalam siklus; setelahnya karyawan diangkat tetap atau kontrak ulang ke K1. */
const AKHIR_SIKLUS_KONTRAK = 5;

function KonfirmasiRow({
    konfirmasi,
    dipilih,
    onPilih,
}: {
    konfirmasi: KonfirmasiKontrakCuti;
    dipilih: boolean;
    onPilih: (dipilih: boolean) => void;
}) {
    const [catatan, setCatatan] = useState('');
    const [tanggalAkhirKontrakBaru, setTanggalAkhirKontrakBaru] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const nama = konfirmasi.karyawan?.nama;
    const akhirK5 = konfirmasi.urutan_kontrak === AKHIR_SIKLUS_KONTRAK;
    const menunggu = konfirmasi.status === 'menunggu';

    const DIALOG: Record<Keputusan, KonfirmasiOptions> = {
        angkat_tetap: {
            title: `Angkat ${nama} menjadi karyawan tetap?`,
            description:
                'Cuti Tahunan berikutnya langsung penuh. Cuti Besar mulai dihitung 5 tahun sejak tanggal pengangkatan.',
            confirmText: 'Angkat Tetap',
        },
        perpanjang: akhirK5
            ? {
                  title: `Kontrak ulang ${nama} ke K1?`,
                  description:
                      'Keadaan khusus: hitungan kontrak kembali ke K1 dan saldo Cuti Tahunan dimulai dari 0 seperti karyawan baru.',
                  confirmText: 'Kontrak Ulang ke K1',
              }
            : {
                  title: `Perpanjang kontrak ${nama}?`,
                  description: `Kontrak baru berlaku hingga ${tanggalAkhirKontrakBaru ? formatDate(tanggalAkhirKontrakBaru) : '-'}.`,
                  confirmText: 'Perpanjang',
              },
        tidak_diperpanjang: {
            title: `Kontrak ${nama} tidak diperpanjang?`,
            description:
                'Karyawan akan dinonaktifkan dan sisa cutinya dibuatkan kompensasi. Keputusan tidak bisa diubah.',
            confirmText: 'Tidak Diperpanjang',
            destructive: true,
        },
    };

    const submit = async (keputusan: Keputusan) => {
        if (!(await tampilkanKonfirmasi(DIALOG[keputusan]))) {
            return;
        }

        setProcessing(true);
        router.post(
            KonfirmasiKontrakController.konfirmasi.url(konfirmasi.id),
            {
                keputusan,
                catatan,
                tanggal_akhir_kontrak_baru:
                    keputusan === 'perpanjang'
                        ? tanggalAkhirKontrakBaru
                        : undefined,
            },
            {
                onError: (err) => setErrors(err),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <div
            className={cn(
                'flex flex-col gap-3 border-l-4 p-4 text-sm md:flex-row md:items-center md:justify-between',
                menunggu
                    ? 'border-l-amber-500 bg-amber-50/40 dark:bg-amber-950/10'
                    : 'border-l-transparent',
            )}
        >
            <div className="flex items-start gap-3">
                {menunggu && (
                    <Checkbox
                        className="mt-0.5"
                        aria-label={`Pilih ${nama}`}
                        checked={dipilih && !akhirK5}
                        disabled={akhirK5}
                        title={
                            akhirK5
                                ? 'Akhir K5 harus diputuskan satu per satu'
                                : undefined
                        }
                        onCheckedChange={(v) => onPilih(v === true)}
                    />
                )}
                <div>
                    <div className="flex flex-wrap items-center gap-2 font-medium">
                        {nama} &middot;{' '}
                        {konfirmasi.saldo_cuti?.jenis_cuti?.nama_jenis}
                        <Badge variant="outline">
                            K{konfirmasi.urutan_kontrak}
                        </Badge>
                    </div>
                    <div className="text-muted-foreground">
                        Periode ke-{konfirmasi.periode_ke} berakhir{' '}
                        {formatDate(konfirmasi.tanggal_batas)}
                    </div>
                    {konfirmasi.karyawan?.tanggal_akhir_kontrak && (
                        <div className="text-xs text-muted-foreground">
                            Kontrak saat ini berakhir{' '}
                            {formatDate(
                                konfirmasi.karyawan.tanggal_akhir_kontrak,
                            )}
                        </div>
                    )}
                    {menunggu && akhirK5 && (
                        <p className="mt-1 text-xs font-medium text-amber-800 dark:text-amber-300">
                            Akhir K5: angkat menjadi karyawan tetap, atau
                            kontrak ulang ke K1 (keadaan khusus, wajib isi
                            alasan).
                        </p>
                    )}
                </div>
            </div>
            {menunggu ? (
                <div className="flex flex-col gap-2 md:items-end">
                    {akhirK5 && (
                        <Button
                            size="sm"
                            className="gap-1.5"
                            disabled={processing}
                            onClick={() => submit('angkat_tetap')}
                        >
                            <UserCheck className="size-4" />
                            Angkat Karyawan Tetap
                        </Button>
                    )}
                    <div className="flex flex-col gap-2 md:flex-row md:items-start">
                        <div className="grid gap-1">
                            <Label
                                htmlFor={`tanggal-akhir-${konfirmasi.id}`}
                                className="text-xs text-muted-foreground"
                            >
                                {akhirK5
                                    ? 'Kontrak ulang (K1) berlaku hingga'
                                    : 'Kontrak baru berlaku hingga'}
                            </Label>
                            <div className="flex gap-1">
                                <Input
                                    id={`tanggal-akhir-${konfirmasi.id}`}
                                    type="date"
                                    min={tanggalSetelah(
                                        konfirmasi.tanggal_batas,
                                    )}
                                    value={tanggalAkhirKontrakBaru}
                                    onChange={(e) =>
                                        setTanggalAkhirKontrakBaru(
                                            e.target.value,
                                        )
                                    }
                                    className="md:w-44"
                                />
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() =>
                                        setTanggalAkhirKontrakBaru(
                                            tambahSatuTahun(
                                                konfirmasi.tanggal_batas,
                                            ),
                                        )
                                    }
                                >
                                    1 Tahun
                                </Button>
                            </div>
                            <InputError
                                message={errors.tanggal_akhir_kontrak_baru}
                            />
                        </div>
                        <div className="grid gap-1">
                            <Label
                                htmlFor={`catatan-${konfirmasi.id}`}
                                className="text-xs text-muted-foreground"
                            >
                                {akhirK5
                                    ? 'Alasan kontrak ulang (wajib)'
                                    : 'Catatan (opsional)'}
                            </Label>
                            <Input
                                id={`catatan-${konfirmasi.id}`}
                                value={catatan}
                                onChange={(e) => setCatatan(e.target.value)}
                                className="md:w-64"
                            />
                            <InputError message={errors.catatan} />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            size="sm"
                            variant={akhirK5 ? 'outline' : 'default'}
                            disabled={
                                processing ||
                                !tanggalAkhirKontrakBaru ||
                                (akhirK5 && catatan.trim() === '')
                            }
                            onClick={() => submit('perpanjang')}
                        >
                            {akhirK5 ? 'Kontrak Ulang ke K1' : 'Perpanjang'}
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            className="border-destructive/50 text-destructive hover:bg-destructive/10 hover:text-destructive"
                            disabled={processing}
                            onClick={() => submit('tidak_diperpanjang')}
                        >
                            Tidak Diperpanjang
                        </Button>
                    </div>
                </div>
            ) : (
                <Badge
                    variant={
                        konfirmasi.status === 'tidak_diperpanjang'
                            ? 'destructive'
                            : 'secondary'
                    }
                >
                    {STATUS_LABEL[konfirmasi.status]}
                </Badge>
            )}
        </div>
    );
}

export default function KonfirmasiKontrakIndex() {
    const { konfirmasiKontraks, jumlahMenunggu } = usePage<PageProps>().props;

    // Akhir K5 tidak bisa ikut perpanjangan massal — wajib diputuskan per orang.
    const menungguDiHalaman = konfirmasiKontraks.data.filter(
        (k) =>
            k.status === 'menunggu' &&
            k.urutan_kontrak !== AKHIR_SIKLUS_KONTRAK,
    );
    const [dipilih, setDipilih] = useState<Set<number>>(new Set());
    const [semua, setSemua] = useState(false);
    const [catatanMassal, setCatatanMassal] = useState('');
    const [errorsMassal, setErrorsMassal] = useState<Record<string, string>>(
        {},
    );
    const [processingMassal, setProcessingMassal] = useState(false);

    const idDipilih = menungguDiHalaman
        .filter((k) => dipilih.has(k.id))
        .map((k) => k.id);
    const jumlahDipilih = semua ? jumlahMenunggu : idDipilih.length;
    const semuaHalamanDipilih =
        menungguDiHalaman.length > 0 &&
        idDipilih.length === menungguDiHalaman.length;

    const pilih = (id: number, nilai: boolean) => {
        setSemua(false);
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

    const pilihSemuaHalaman = (nilai: boolean) => {
        setSemua(false);
        setDipilih(
            nilai ? new Set(menungguDiHalaman.map((k) => k.id)) : new Set(),
        );
    };

    const perpanjangMassal = async () => {
        if (
            !(await tampilkanKonfirmasi({
                title: `Perpanjang kontrak ${jumlahDipilih} karyawan?`,
                description:
                    'Masing-masing diperpanjang 1 tahun dari tanggal berakhir periodenya.',
                confirmText: 'Perpanjang',
            }))
        ) {
            return;
        }

        router.post(
            KonfirmasiKontrakController.perpanjangMassal.url(),
            {
                semua,
                konfirmasi_ids: semua ? [] : idDipilih,
                catatan: catatanMassal || undefined,
            },
            {
                preserveScroll: true,
                onStart: () => setProcessingMassal(true),
                onFinish: () => setProcessingMassal(false),
                onError: (err) => setErrorsMassal(err),
                onSuccess: () => {
                    setDipilih(new Set());
                    setSemua(false);
                    setCatatanMassal('');
                    setErrorsMassal({});
                },
            },
        );
    };

    return (
        <>
            <Head title="Konfirmasi Perpanjangan Kontrak" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <CalendarClock className="size-5 text-amber-600 dark:text-amber-400" />
                    Konfirmasi Perpanjangan Kontrak
                </h1>
                <p className="text-sm text-muted-foreground">
                    Karyawan kontrak yang periode cuti tahunannya sudah berakhir
                    tertahan di sini sampai HRD mengonfirmasi status
                    perpanjangan kontraknya. Kontrak berjalan dari K1 sampai K5;
                    setelah K5 karyawan diangkat tetap atau, dalam keadaan
                    khusus, kontrak ulang ke K1.
                </p>

                {jumlahMenunggu > 0 && (
                    <Card>
                        <CardContent className="flex flex-col gap-3">
                            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                <label className="flex cursor-pointer items-center gap-2 font-medium">
                                    <Checkbox
                                        checked={semuaHalamanDipilih && !semua}
                                        disabled={
                                            menungguDiHalaman.length === 0
                                        }
                                        onCheckedChange={(v) =>
                                            pilihSemuaHalaman(v === true)
                                        }
                                    />
                                    Pilih semua di halaman ini (
                                    {menungguDiHalaman.length})
                                </label>
                                <label className="flex cursor-pointer items-center gap-2 font-medium">
                                    <Checkbox
                                        checked={semua}
                                        onCheckedChange={(v) => {
                                            setSemua(v === true);
                                            setDipilih(new Set());
                                        }}
                                    />
                                    Semua yang menunggu ({jumlahMenunggu})
                                </label>
                            </div>
                            <div className="flex flex-col gap-2 md:flex-row md:items-start">
                                <div className="grid flex-1 gap-1">
                                    <Input
                                        placeholder="Catatan (opsional), mis. Perpanjangan kontrak gelombang Oktober"
                                        value={catatanMassal}
                                        onChange={(e) =>
                                            setCatatanMassal(e.target.value)
                                        }
                                    />
                                    <InputError
                                        message={
                                            errorsMassal.konfirmasi_ids ??
                                            errorsMassal.catatan
                                        }
                                    />
                                </div>
                                <Button
                                    className="gap-1.5"
                                    disabled={
                                        processingMassal || jumlahDipilih === 0
                                    }
                                    onClick={perpanjangMassal}
                                >
                                    <CalendarPlus className="size-4" />
                                    Perpanjang 1 Tahun ({jumlahDipilih})
                                </Button>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Kontrak baru tiap karyawan berlaku hingga tepat
                                1 tahun setelah periodenya berakhir. Untuk
                                tanggal lain atau &quot;Tidak
                                Diperpanjang&quot;, gunakan form di
                                masing-masing baris. Karyawan di akhir K5 selalu
                                dilewati dan harus diputuskan satu per satu.
                            </p>
                        </CardContent>
                    </Card>
                )}

                <Card className="gap-0 overflow-hidden py-0">
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
                                dipilih={semua || dipilih.has(konfirmasi.id)}
                                onPilih={(nilai) => pilih(konfirmasi.id, nilai)}
                            />
                        ))}
                    </CardContent>
                </Card>

                <Pagination
                    links={konfirmasiKontraks.links}
                    perPage={konfirmasiKontraks.per_page}
                />
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
