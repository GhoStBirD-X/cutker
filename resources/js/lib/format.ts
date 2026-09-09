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
