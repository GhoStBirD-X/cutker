export type SaldoSeverity = 'unlimited' | 'safe' | 'warning' | 'critical';

/**
 * Urutan urgensi (makin besar makin mendesak), dipakai untuk mengurutkan
 * atau mengambil status "terburuk" dari beberapa saldo sekaligus.
 */
export const SEVERITY_RANK: Record<SaldoSeverity, number> = {
    unlimited: 0,
    safe: 1,
    warning: 2,
    critical: 3,
};

export type SaldoSeverityStyle = {
    severity: SaldoSeverity;
    label: string;
    dot: string;
    text: string;
    bar: string;
    border: string;
    badge: string;
};

const STYLES: Record<SaldoSeverity, Omit<SaldoSeverityStyle, 'severity'>> = {
    unlimited: {
        label: 'Tanpa batas',
        dot: 'bg-sky-500',
        text: 'text-sky-700 dark:text-sky-400',
        bar: 'bg-sky-500',
        border: 'border-sky-200 dark:border-sky-900',
        badge: 'border-sky-200 bg-sky-100 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',
    },
    safe: {
        label: 'Saldo aman',
        dot: 'bg-blue-500',
        text: 'text-blue-700 dark:text-blue-400',
        bar: 'bg-blue-500',
        border: 'border-blue-200 dark:border-blue-900',
        badge: 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300',
    },
    warning: {
        label: 'Saldo menipis',
        dot: 'bg-amber-500',
        text: 'text-amber-700 dark:text-amber-400',
        bar: 'bg-amber-500',
        border: 'border-amber-200 dark:border-amber-900',
        badge: 'border-amber-200 bg-amber-100 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    },
    critical: {
        label: 'Saldo minus',
        dot: 'bg-red-500',
        text: 'text-red-700 dark:text-red-400',
        bar: 'bg-red-500',
        border: 'border-red-200 dark:border-red-900',
        badge: 'border-red-200 bg-red-100 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    },
};

/**
 * Kuota/sisa null berarti jenis cuti tanpa batas (mis. cuti khusus) — bukan
 * "belum diisi", jadi tidak boleh dianggap warning/critical.
 */
export function saldoSeverity(
    sisa: number | null,
    kuota: number | null,
): SaldoSeverityStyle {
    if (sisa === null || kuota === null) {
        return { severity: 'unlimited', ...STYLES.unlimited };
    }

    if (sisa < 0) {
        return { severity: 'critical', ...STYLES.critical };
    }

    const ratio = kuota > 0 ? sisa / kuota : sisa > 0 ? 1 : 0;

    if (ratio <= 0.25) {
        return { severity: 'warning', ...STYLES.warning };
    }

    return { severity: 'safe', ...STYLES.safe };
}

export function saldoProgress(
    sisa: number | null,
    kuota: number | null,
): number {
    if (sisa === null || kuota === null || kuota <= 0) {
        return 100;
    }

    return Math.max(0, Math.min(100, (sisa / kuota) * 100));
}
