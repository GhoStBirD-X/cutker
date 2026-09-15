import {
    AlertTriangle,
    CheckCircle2,
    Infinity as InfinityIcon,
    TrendingDown,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { saldoProgress, saldoSeverity } from '@/lib/saldo-severity';
import { cn } from '@/lib/utils';

const ICONS = {
    unlimited: InfinityIcon,
    safe: CheckCircle2,
    warning: AlertTriangle,
    critical: TrendingDown,
};

type SaldoCutiMeterProps = {
    nama: string;
    sisa: number | null;
    kuota: number | null;
    terpakai: number;
    className?: string;
};

/**
 * Kartu ringkas satu jenis cuti, dengan warna yang mencerminkan urgensinya:
 * biru = aman, kuning = menipis (<=25% kuota), merah = minus.
 */
export function SaldoCutiMeter({
    nama,
    sisa,
    kuota,
    terpakai,
    className,
}: SaldoCutiMeterProps) {
    const style = saldoSeverity(sisa, kuota);
    const progress = saldoProgress(sisa, kuota);
    const Icon = ICONS[style.severity];

    return (
        <Card
            className={cn(
                'overflow-hidden border-t-4 transition-shadow hover:shadow-md',
                style.border,
                className,
            )}
        >
            <CardHeader className="pb-2">
                <CardTitle className="flex items-center justify-between gap-2 text-sm font-medium">
                    <span className="truncate">{nama}</span>
                    <Icon className={cn('size-4 shrink-0', style.text)} />
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className={cn('text-2xl font-bold', style.text)}>
                    {sisa ?? '∞'} {sisa !== null && 'hari'}
                </div>
                <p className="text-xs text-muted-foreground">
                    dari kuota {kuota ?? 'tanpa batas'} hari, terpakai{' '}
                    {terpakai}
                </p>
                <div className="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className={cn(
                            'h-full rounded-full transition-all',
                            style.bar,
                        )}
                        style={{ width: `${progress}%` }}
                    />
                </div>
            </CardContent>
        </Card>
    );
}

type SaldoCutiInlineProps = {
    nama: string;
    sisa: number | null;
    kuota: number | null;
    terpakai: number;
    detail?: string;
    className?: string;
};

/**
 * Baris ringkas (dot + teks) untuk daftar padat seperti Master/Laporan Saldo
 * Cuti, memakai warna urgensi yang sama dengan SaldoCutiMeter.
 */
export function SaldoCutiInline({
    nama,
    sisa,
    kuota,
    terpakai,
    detail,
    className,
}: SaldoCutiInlineProps) {
    const style = saldoSeverity(sisa, kuota);

    return (
        <div className={cn('flex items-center gap-2 text-sm', className)}>
            <span
                className={cn(
                    'inline-block size-2 shrink-0 rounded-full',
                    style.dot,
                )}
                aria-hidden
                title={style.label}
            />
            <span className="text-muted-foreground">
                {nama ? (
                    <span className="font-medium text-foreground">
                        {nama}:{' '}
                    </span>
                ) : null}
                kuota {kuota ?? 'tanpa batas'}, terpakai {terpakai},{' '}
                <span className={cn('font-semibold', style.text)}>
                    sisa {sisa ?? 'tanpa batas'}
                </span>
                {detail ? <> &middot; {detail}</> : null}
            </span>
        </div>
    );
}
