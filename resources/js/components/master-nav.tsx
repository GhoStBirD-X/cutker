import { Link, usePage } from '@inertiajs/react';
import {
    Briefcase,
    Building2,
    CalendarRange,
    Clock,
    MessageSquareText,
    PartyPopper,
    ShieldCheck,
    Users,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
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

    const items: {
        title: string;
        href: ReturnType<typeof karyawanIndex>;
        icon: LucideIcon;
    }[] = [
        { title: 'Karyawan', href: karyawanIndex(), icon: Users },
        { title: 'Departemen', href: departemenIndex(), icon: Building2 },
        { title: 'Jabatan', href: jabatanIndex(), icon: Briefcase },
        { title: 'Jenis Cuti', href: jenisCutiIndex(), icon: CalendarRange },
        {
            title: 'Alasan Cuti',
            href: alasanCutiIndex(),
            icon: MessageSquareText,
        },
        { title: 'Saldo Cuti', href: saldoCutiIndex(), icon: Wallet },
        { title: 'Hari Libur', href: hariLiburIndex(), icon: PartyPopper },
        { title: 'Shift', href: shiftIndex(), icon: Clock },
        ...(auth.roles.includes('admin')
            ? [
                  {
                      title: 'User & Role',
                      href: usersIndex(),
                      icon: ShieldCheck,
                  },
              ]
            : []),
    ];

    return (
        <nav className="flex gap-1 overflow-x-auto border-b">
            {items.map((item) => (
                <Link
                    key={item.title}
                    href={item.href}
                    className={cn(
                        'flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                        isCurrentUrl(item.href)
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground',
                    )}
                >
                    <item.icon className="size-4" />
                    {item.title}
                </Link>
            ))}
        </nav>
    );
}
