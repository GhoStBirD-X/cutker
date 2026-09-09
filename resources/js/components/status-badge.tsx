import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { StatusApproval, StatusPengajuan } from '@/types';

const LABELS: Record<StatusPengajuan | StatusApproval, string> = {
    pending: 'Menunggu',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
    dibatalkan: 'Dibatalkan',
};

const CLASSES: Record<StatusPengajuan | StatusApproval, string> = {
    pending:
        'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-900',
    disetujui:
        'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-900',
    ditolak:
        'bg-red-100 text-red-800 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-900',
    dibatalkan: 'bg-muted text-muted-foreground border-transparent',
};

export function StatusBadge({
    status,
}: {
    status: StatusPengajuan | StatusApproval;
}) {
    return <Badge className={cn(CLASSES[status])}>{LABELS[status]}</Badge>;
}
