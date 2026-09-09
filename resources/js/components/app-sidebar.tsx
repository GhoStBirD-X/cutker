import { Link, usePage } from '@inertiajs/react';
import {
    Banknote,
    Building2,
    CalendarDays,
    ClipboardCheck,
    FileBarChart,
    FilePlus2,
    LayoutGrid,
    ListChecks,
    UserCheck,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as approvalIndex } from '@/routes/approval';
import { create as cutiCreate, index as cutiIndex } from '@/routes/cuti';
import { index as kompensasiIndex } from '@/routes/cuti/kompensasi';
import { index as konfirmasiKontrakIndex } from '@/routes/cuti/konfirmasi-kontrak';
import { index as jadwalShiftIndex } from '@/routes/jadwal-shift';
import { index as laporanIndex } from '@/routes/laporan';
import { index as karyawanIndex } from '@/routes/master/karyawan';
import { index as usersIndex } from '@/routes/users';
import type { Auth, NavItem, Role } from '@/types';

function buildNavItems(roles: Role[]): NavItem[] {
    const items: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        { title: 'Riwayat Cuti', href: cutiIndex(), icon: ListChecks },
        { title: 'Ajukan Cuti', href: cutiCreate(), icon: FilePlus2 },
    ];

    if (
        roles.includes('kepala_bagian') ||
        roles.includes('hrd') ||
        roles.includes('manager')
    ) {
        items.push({
            title: 'Approval',
            href: approvalIndex(),
            icon: ClipboardCheck,
        });
    }

    items.push({
        title: 'Jadwal Shift',
        href: jadwalShiftIndex(),
        icon: CalendarDays,
    });

    if (roles.includes('hrd') || roles.includes('admin')) {
        items.push({
            title: 'Master Data',
            href: karyawanIndex(),
            icon: Building2,
        });
        items.push({
            title: 'Konfirmasi Kontrak',
            href: konfirmasiKontrakIndex(),
            icon: UserCheck,
        });
        items.push({
            title: 'Kompensasi Cuti',
            href: kompensasiIndex(),
            icon: Banknote,
        });
    }

    if (
        roles.includes('hrd') ||
        roles.includes('admin') ||
        roles.includes('manager')
    ) {
        items.push({
            title: 'Laporan',
            href: laporanIndex(),
            icon: FileBarChart,
        });
    }

    if (roles.includes('admin')) {
        items.push({ title: 'Kelola User', href: usersIndex(), icon: Users });
    }

    return items;
}

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const mainNavItems = buildNavItems(auth.roles ?? []);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={[]} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
