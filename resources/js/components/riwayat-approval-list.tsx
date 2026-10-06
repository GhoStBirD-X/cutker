import { StatusBadge } from '@/components/status-badge';
import {
    approvalLevelLabel,
    approverDisplayName,
    formatDateTime,
} from '@/lib/format';
import type { Approval } from '@/types';

/** Daftar jejak approval per level, lengkap dengan waktu keputusan & catatan. */
export function RiwayatApprovalList({ approvals }: { approvals: Approval[] }) {
    if (approvals.length === 0) {
        return (
            <p className="p-4 text-sm text-muted-foreground">
                Belum ada riwayat approval.
            </p>
        );
    }

    return approvals.map((approval) => (
        <div
            key={approval.id}
            className="flex flex-col gap-2 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div className="font-medium">
                    Level {approval.level} ({approvalLevelLabel(approval.level)}
                    ) &middot; {approverDisplayName(approval)}
                </div>
                {approval.tanggal_approval && (
                    <div className="text-xs text-muted-foreground">
                        {formatDateTime(approval.tanggal_approval)}
                    </div>
                )}
                {approval.catatan && (
                    <div className="text-muted-foreground">
                        Catatan: {approval.catatan}
                    </div>
                )}
            </div>
            <StatusBadge status={approval.status} />
        </div>
    ));
}
