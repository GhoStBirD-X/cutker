import { Badge } from '@/components/ui/badge';

/**
 * Penanda visual untuk kuota/jumlah hari yang tidak dibatasi (nilai null),
 * dipakai di sebelah kata "hari" pada daftar jenis cuti & alasan cuti.
 */
export function UnlimitedBadge() {
    return (
        <Badge
            variant="outline"
            className="border-violet-200 bg-violet-100 text-violet-800 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-300"
        >
            ∞ Tanpa Batas
        </Badge>
    );
}
