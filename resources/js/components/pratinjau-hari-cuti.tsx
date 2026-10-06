import { AlertTriangle, CalendarCheck } from 'lucide-react';
import { hitungHariKerja } from '@/lib/hari-kerja';
import { saldoSeverity } from '@/lib/saldo-severity';
import { cn } from '@/lib/utils';
import type { SaldoCuti } from '@/types';

type PratinjauHariCutiProps = {
    tanggalMulai: string;
    tanggalSelesai: string;
    hariLibur: string[];
    /** Saldo aktif untuk jenis cuti terpilih; kosong jika belum memilih jenis cuti. */
    saldo?: SaldoCuti;
};

/**
 * Pratinjau sebelum kirim: berapa hari kerja yang akan terpotong dan sisa
 * saldo setelahnya, supaya karyawan tidak baru tahu setelah pengajuan dibuat.
 */
export function PratinjauHariCuti({
    tanggalMulai,
    tanggalSelesai,
    hariLibur,
    saldo,
}: PratinjauHariCutiProps) {
    const rincian = hitungHariKerja(tanggalMulai, tanggalSelesai, hariLibur);

    if (!rincian) {
        return null;
    }

    const terbatas = saldo && saldo.kuota !== null && saldo.sisa !== null;
    const sisaSetelah = terbatas ? saldo.sisa! - rincian.hariKerja : null;
    const tidakCukup = sisaSetelah !== null && sisaSetelah < 0;

    return (
        <div
            role="status"
            className={cn(
                'rounded-md border px-3 py-2 text-sm',
                tidakCukup
                    ? 'border-destructive/50 bg-destructive/10'
                    : 'bg-muted/40',
            )}
        >
            <div className="flex items-center gap-2 font-medium">
                <CalendarCheck className="size-4 text-muted-foreground" />
                {rincian.hariKerja} hari kerja dipotong dari saldo
            </div>
            {rincian.hariLibur > 0 && (
                <p className="text-xs text-muted-foreground">
                    Dari {rincian.hariKalender} hari kalender, dikurangi{' '}
                    {rincian.hariLibur} hari Sabtu/Minggu/libur.
                </p>
            )}
            {terbatas && (
                <p
                    className={cn(
                        'mt-1 flex items-center gap-1.5 text-xs',
                        tidakCukup
                            ? 'font-medium text-destructive'
                            : saldoSeverity(sisaSetelah, saldo.kuota).text,
                    )}
                >
                    {tidakCukup && <AlertTriangle className="size-3.5" />}
                    {tidakCukup
                        ? `Saldo tidak cukup: sisa ${saldo.sisa} hari, butuh ${rincian.hariKerja} hari.`
                        : `Sisa saldo setelah pengajuan: ${sisaSetelah} dari ${saldo.kuota} hari.`}
                </p>
            )}
        </div>
    );
}
