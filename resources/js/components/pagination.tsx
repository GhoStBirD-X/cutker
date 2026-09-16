import { Link, router } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types';

const PILIHAN_PER_HALAMAN = [10, 20, 30, 50];

type PaginationProps = {
    links: PaginationLink[];
    /** Sertakan untuk menampilkan selektor "baris per halaman" di sebelah navigasi. */
    perPage?: number;
};

export function Pagination({ links, perPage }: PaginationProps) {
    const ubahJumlahPerHalaman = (value: string) => {
        const params = new URLSearchParams(window.location.search);
        params.set('per_page', value);
        params.delete('page');

        router.get(
            `${window.location.pathname}?${params.toString()}`,
            {},
            { preserveState: true, preserveScroll: true },
        );
    };

    if (links.length <= 3 && perPage === undefined) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            {links.length > 3 ? (
                <nav className="flex flex-wrap items-center gap-1">
                    {links.map((link, index) => (
                        <Link
                            key={index}
                            href={link.url ?? '#'}
                            preserveScroll
                            className={cn(
                                'rounded-md px-3 py-1.5 text-sm',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                                !link.url && 'pointer-events-none opacity-40',
                            )}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </nav>
            ) : (
                <div />
            )}
            {perPage !== undefined && (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Baris per halaman</span>
                    <Select
                        value={String(perPage)}
                        onValueChange={ubahJumlahPerHalaman}
                    >
                        <SelectTrigger size="sm" className="w-18">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {PILIHAN_PER_HALAMAN.map((opsi) => (
                                <SelectItem key={opsi} value={String(opsi)}>
                                    {opsi}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            )}
        </div>
    );
}
