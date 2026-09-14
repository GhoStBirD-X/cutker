const dateFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

export function formatDate(value: string): string {
    return dateFormatter.format(new Date(value));
}

export function formatDateTime(value: string): string {
    return dateTimeFormatter.format(new Date(value));
}

const APPROVAL_LEVEL_LABELS: Record<number, string> = {
    1: 'Kepala Bagian',
    2: 'HRD',
    3: 'Manager',
};

export function approvalLevelLabel(level: number): string {
    return APPROVAL_LEVEL_LABELS[level] ?? `Level ${level}`;
}

// Level HRD (2) dan Manager (3) adalah kolam bersama: approver_id yang
// tersimpan pada baris masih pending cuma "tebakan awal" sistem (siapa yang
// bebannya paling ringan), bukan penugasan eksklusif. Menampilkan nama
// spesifik untuk baris pending di level ini menyesatkan (seolah ditujukan
// ke satu orang/departemen saja), jadi tampilkan label generik sampai ada
// yang benar-benar bertindak (approver_id baru dicatat ulang saat itu).
const LEVEL_KOLAM_BERSAMA: Record<number, string> = {
    2: 'Menunggu HRD',
    3: 'Menunggu Manager',
};

export function approverDisplayName(approval: {
    level: number;
    status: string;
    approver?: { nama: string } | null;
}): string {
    if (approval.status === 'pending' && LEVEL_KOLAM_BERSAMA[approval.level]) {
        return LEVEL_KOLAM_BERSAMA[approval.level];
    }

    return approval.approver?.nama ?? 'Belum ditentukan';
}
