import { Link, usePage } from '@inertiajs/react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import { index as alasanCutiIndex } from '@/routes/master/alasan-cuti';
import { index as departemenIndex } from '@/routes/master/departemen';
import { index as hariLiburIndex } from '@/routes/master/hari-libur';
import { index as jabatanIndex } from '@/routes/master/jabatan';
import { index as jenisCutiIndex } from '@/routes/master/jenis-cuti';
import { index as karyawanIndex } from '@/routes/master/karyawan';
import { index as saldoCutiIndex } from '@/routes/master/saldo-cuti';
import { index as shiftIndex } from '@/routes/master/shift';
import { index as usersIndex } from '@/routes/users';
import type { Auth } from '@/types';

export function MasterNav() {
    const { isCurrentUrl } = useCurrentUrl();
    const { auth } = usePage<{ auth: Auth }>().props;

    const items = [
        { title: 'Karyawan', href: karyawanIndex() },
        { title: 'Departemen', href: departemenIndex() },
        { title: 'Jabatan', href: jabatanIndex() },
        { title: 'Jenis Cuti', href: jenisCutiIndex() },
        { title: 'Alasan Cuti', href: alasanCutiIndex() },
        { title: 'Saldo Cuti', href: saldoCutiIndex() },
        { title: 'Hari Libur', href: hariLiburIndex() },
        { title: 'Shift', href: shiftIndex() },
        ...(auth.roles.includes('admin')
            ? [{ title: 'User & Role', href: usersIndex() }]
            : []),
    ];

    return (
        <nav className="flex gap-1 overflow-x-auto border-b">
            {items.map((item) => (
                <Link
                    key={item.title}
                    href={item.href}
                    className={cn(
                        'shrink-0 border-b-2 px-3 py-2 text-sm font-medium whitespace-nowrap',
                        isCurrentUrl(item.href)
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground',
                    )}
                >
                    {item.title}
                </Link>
            ))}
        </nav>
    );
}
