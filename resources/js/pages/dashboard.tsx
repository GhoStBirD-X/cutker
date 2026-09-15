import type { InertiaLinkProps } from '@inertiajs/react';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowRight,
    Building2,
    CalendarClock,
    CalendarPlus,
    ClipboardCheck,
    FileClock,
    ListChecks,
} from 'lucide-react';
import { SaldoCutiMeter } from '@/components/saldo-cuti-meter';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as approvalIndex } from '@/routes/approval';
import { show as cutiShow } from '@/routes/cuti';
import { index as kompensasiIndex } from '@/routes/cuti/kompensasi';
import { index as konfirmasiKontrakIndex } from '@/routes/cuti/konfirmasi-kontrak';
import type { Auth, PengajuanCuti, RiwayatSaldoCuti, SaldoCuti } from '@/types';

type RingkasanPabrik = {
    total_karyawan_aktif: number;
    total_pengajuan_pending: number;
    total_pengajuan_bulan_ini: number;
};

type PageProps = {
    auth: Auth;
    saldoCuti?: SaldoCuti[];
    riwayatCuti?: PengajuanCuti[];
    resumeCuti?: RiwayatSaldoCuti[];
    menungguKonfirmasiKontrak?: boolean;
    approvalPendingCount?: number;
    ringkasanPabrik?: RingkasanPabrik;
    konfirmasiKontrakPendingCount?: number;
    kompensasiCutiPendingCount?: number;
};

export default function Dashboard() {
    const {
        auth,
        saldoCuti,
        riwayatCuti,
        resumeCuti,
        menungguKonfirmasiKontrak,
        approvalPendingCount,
        ringkasanPabrik,
        konfirmasiKontrakPendingCount,
        kompensasiCutiPendingCount,
    } = usePage<PageProps>().props;

    const adaTindakan =
        typeof approvalPendingCount === 'number' ||
        typeof konfirmasiKontrakPendingCount === 'number' ||
        typeof kompensasiCutiPendingCount === 'number';

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-8 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        Halo, {auth.user.karyawan?.nama ?? auth.user.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Ringkasan cuti dan aktivitas Anda.
                    </p>
                </div>

                {menungguKonfirmasiKontrak && (
                    <Card className="border-amber-300 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40">
                        <CardContent className="flex items-start gap-3 text-sm">
                            <AlertCircle className="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                            <div>
                                <p className="font-medium text-amber-900 dark:text-amber-200">
                                    Kontrak kerja Anda menunggu konfirmasi
                                </p>
                                <p className="text-amber-800/80 dark:text-amber-300/80">
                                    Perpanjangan kontrak Anda sedang diproses
                                    HRD. Saldo cuti tahunan baru direset setelah
                                    konfirmasi selesai.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {adaTindakan && (
                    <section>
                        <h2 className="mb-2 flex items-center gap-1.5 text-sm font-semibold text-foreground">
                            <ListChecks className="size-4" />
                            Perlu Tindakan Anda
                        </h2>
                        <div className="grid gap-4 sm:grid-cols-3">
                            {typeof approvalPendingCount === 'number' && (
                                <TindakanCard
                                    href={approvalIndex()}
                                    icon={ClipboardCheck}
                                    label="Approval Menunggu Tindakan"
                                    count={approvalPendingCount}
                                />
                            )}
                            {typeof konfirmasiKontrakPendingCount ===
                                'number' && (
                                <TindakanCard
                                    href={konfirmasiKontrakIndex()}
                                    icon={CalendarClock}
                                    label="Menunggu Konfirmasi Kontrak"
                                    count={konfirmasiKontrakPendingCount}
                                />
                            )}
                            {typeof kompensasiCutiPendingCount === 'number' && (
                                <TindakanCard
                                    href={kompensasiIndex()}
                                    icon={FileClock}
                                    label="Kompensasi Cuti Menunggu Diproses"
                                    count={kompensasiCutiPendingCount}
                                />
                            )}
                        </div>
                    </section>
                )}

                {saldoCuti && (
                    <section>
                        <h2 className="mb-2 flex items-center gap-1.5 text-sm font-semibold text-foreground">
                            <CalendarPlus className="size-4" />
                            Sisa Saldo Cuti Berjalan
                        </h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {saldoCuti.map((saldo) => (
                                <SaldoCutiMeter
                                    key={saldo.id}
                                    nama={saldo.jenis_cuti?.nama_jenis ?? ''}
                                    sisa={saldo.sisa}
                                    kuota={saldo.kuota}
                                    terpakai={saldo.terpakai}
                                />
                            ))}
                        </div>
                    </section>
                )}

                {riwayatCuti && (
                    <section>
                        <h2 className="mb-2 text-sm font-semibold text-muted-foreground">
                            Riwayat Pengajuan Terbaru
                        </h2>
                        <Card>
                            <CardContent className="divide-y p-0">
                                {riwayatCuti.length === 0 && (
                                    <p className="p-4 text-sm text-muted-foreground">
                                        Belum ada pengajuan cuti.
                                    </p>
                                )}
                                {riwayatCuti.map((pengajuan) => (
                                    <Link
                                        key={pengajuan.id}
                                        href={cutiShow(pengajuan.id)}
                                        className="flex flex-col gap-2 p-4 text-sm transition-colors hover:bg-accent sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <div className="font-medium">
                                                {
                                                    pengajuan.jenis_cuti
                                                        ?.nama_jenis
                                                }
                                            </div>
                                            <div className="text-muted-foreground">
                                                {formatDate(
                                                    pengajuan.tanggal_mulai,
                                                )}{' '}
                                                s/d{' '}
                                                {formatDate(
                                                    pengajuan.tanggal_selesai,
                                                )}{' '}
                                                ({pengajuan.jumlah_hari} hari)
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <StatusBadge
                                                status={pengajuan.status}
                                            />
                                            <ArrowRight className="size-4 shrink-0 text-muted-foreground" />
                                        </div>
                                    </Link>
                                ))}
                            </CardContent>
                        </Card>
                    </section>
                )}

                {resumeCuti && resumeCuti.length > 0 && (
                    <section>
                        <h2 className="mb-2 text-sm font-semibold text-muted-foreground">
                            Resume Cuti Tahunan
                        </h2>
                        <Card>
                            <CardContent className="divide-y p-0">
                                {resumeCuti.map((riwayat) => (
                                    <div
                                        key={riwayat.id}
                                        className="flex flex-col gap-2 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <div className="font-medium">
                                                {riwayat.jenis_cuti?.nama_jenis}{' '}
                                                &middot; Periode ke-
                                                {riwayat.periode_ke}
                                            </div>
                                            <div className="text-muted-foreground">
                                                {formatDate(
                                                    riwayat.periode_mulai,
                                                )}{' '}
                                                s/d{' '}
                                                {formatDate(
                                                    riwayat.periode_selesai,
                                                )}
                                            </div>
                                        </div>
                                        <Badge variant="outline">
                                            Terpakai {riwayat.terpakai}/
                                            {riwayat.kuota} hari
                                        </Badge>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </section>
                )}

                {ringkasanPabrik && (
                    <section>
                        <h2 className="mb-2 flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            <Building2 className="size-3.5" />
                            Ringkasan Pabrik
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-3">
                            <RingkasanStat
                                label="Karyawan Aktif"
                                value={ringkasanPabrik.total_karyawan_aktif}
                            />
                            <RingkasanStat
                                label="Pengajuan Pending"
                                value={ringkasanPabrik.total_pengajuan_pending}
                            />
                            <RingkasanStat
                                label="Pengajuan Bulan Ini"
                                value={
                                    ringkasanPabrik.total_pengajuan_bulan_ini
                                }
                            />
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

type TindakanCardProps = {
    href: NonNullable<InertiaLinkProps['href']>;
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    count: number;
};

/**
 * Kartu tier "penting" — dipakai untuk hal yang menunggu tindakan aktif
 * pengguna, sengaja dibuat lebih mencolok (border & aksen amber) daripada
 * kartu statistik biasa supaya langsung menarik perhatian.
 */
function TindakanCard({ href, icon: Icon, label, count }: TindakanCardProps) {
    const perluPerhatian = count > 0;

    return (
        <Link href={href}>
            <Card
                className={cn(
                    'h-full border-l-4 transition-all hover:-translate-y-0.5 hover:shadow-md',
                    perluPerhatian
                        ? 'border-l-amber-500 bg-amber-50/60 dark:bg-amber-950/20'
                        : 'border-l-transparent',
                )}
            >
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center justify-between text-sm font-medium text-muted-foreground">
                        <span className="flex items-center gap-1.5">
                            <Icon
                                className={cn(
                                    'size-4',
                                    perluPerhatian &&
                                        'text-amber-600 dark:text-amber-400',
                                )}
                            />
                            {label}
                        </span>
                        <ArrowRight className="size-4 text-muted-foreground" />
                    </CardTitle>
                </CardHeader>
                <CardContent
                    className={cn(
                        'text-2xl font-bold',
                        perluPerhatian && 'text-amber-700 dark:text-amber-400',
                    )}
                >
                    {count}
                </CardContent>
            </Card>
        </Link>
    );
}

/**
 * Statistik latar belakang (bukan untuk ditindaklanjuti) — sengaja dibuat
 * lebih kecil & senyap dibanding kartu tindakan/saldo di atasnya.
 */
function RingkasanStat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-lg border bg-muted/30 px-4 py-3">
            <div className="text-lg font-semibold">{value}</div>
            <div className="text-xs text-muted-foreground">{label}</div>
        </div>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
