import { Link, usePage } from '@inertiajs/react';
import {
    Banknote,
    Building2,
    CalendarDays,
    CalendarRange,
    ClipboardCheck,
    FileBarChart,
    FilePlus2,
    LayoutGrid,
    ListChecks,
    UserCheck,
    Wallet,
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
import { index as cutiMassalIndex } from '@/routes/cuti/massal';
import { index as jadwalShiftIndex } from '@/routes/jadwal-shift';
import {
    index as laporanIndex,
    saldoCuti as saldoCutiLaporanIndex,
} from '@/routes/laporan';
import { index as alasanCutiIndex } from '@/routes/master/alasan-cuti';
import { index as departemenIndex } from '@/routes/master/departemen';
import { index as hariLiburIndex } from '@/routes/master/hari-libur';
import { index as jabatanIndex } from '@/routes/master/jabatan';
import { index as jenisCutiIndex } from '@/routes/master/jenis-cuti';
import { index as karyawanIndex } from '@/routes/master/karyawan';
import { index as saldoCutiMasterIndex } from '@/routes/master/saldo-cuti';
import { index as shiftIndex } from '@/routes/master/shift';
import { index as usersIndex } from '@/routes/users';
import type { Auth, NavItem, Role } from '@/types';

type NavGroup = { label: string; items: NavItem[] };

function buildNavGroups(roles: Role[]): NavGroup[] {
    const isHrdAdmin = roles.includes('hrd') || roles.includes('admin');
    const groups: NavGroup[] = [];

    const menuItems: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        { title: 'Riwayat Cuti', href: cutiIndex(), icon: ListChecks },
        { title: 'Ajukan Cuti', href: cutiCreate(), icon: FilePlus2 },
    ];

    if (
        roles.includes('kepala_bagian') ||
        roles.includes('hrd') ||
        roles.includes('manager')
    ) {
        menuItems.push({
            title: 'Approval',
            href: approvalIndex(),
            icon: ClipboardCheck,
        });
    }

    menuItems.push({
        title: 'Jadwal Shift',
        href: jadwalShiftIndex(),
        icon: CalendarDays,
    });

    groups.push({ label: 'Menu', items: menuItems });

    if (isHrdAdmin) {
        const masterDataChildren: NavItem[] = [
            { title: 'Karyawan', href: karyawanIndex() },
            { title: 'Departemen', href: departemenIndex() },
            { title: 'Jabatan', href: jabatanIndex() },
            { title: 'Jenis Cuti', href: jenisCutiIndex() },
            { title: 'Alasan Cuti', href: alasanCutiIndex() },
            { title: 'Saldo Cuti', href: saldoCutiMasterIndex() },
            { title: 'Hari Libur', href: hariLiburIndex() },
            { title: 'Shift', href: shiftIndex() },
        ];

        if (roles.includes('admin')) {
            masterDataChildren.push({
                title: 'User & Role',
                href: usersIndex(),
            });
        }

        groups.push({
            label: 'Manajemen',
            items: [
                {
                    title: 'Master Data',
                    href: karyawanIndex(),
                    icon: Building2,
                    children: masterDataChildren,
                },
                {
                    title: 'Konfirmasi Kontrak',
                    href: konfirmasiKontrakIndex(),
                    icon: UserCheck,
                },
                {
                    title: 'Kompensasi Cuti',
                    href: kompensasiIndex(),
                    icon: Banknote,
                },
                {
                    title: 'Cuti Massal',
                    href: cutiMassalIndex(),
                    icon: CalendarRange,
                },
            ],
        });
    }

    const laporanItems: NavItem[] = [];

    if (isHrdAdmin || roles.includes('manager')) {
        laporanItems.push({
            title: 'Laporan',
            href: laporanIndex(),
            icon: FileBarChart,
        });
    }

    if (
        isHrdAdmin ||
        roles.includes('manager') ||
        roles.includes('kepala_bagian')
    ) {
        laporanItems.push({
            title: 'Saldo Cuti',
            href: saldoCutiLaporanIndex(),
            icon: Wallet,
        });
    }

    if (laporanItems.length > 0) {
        groups.push({ label: 'Laporan', items: laporanItems });
    }

    return groups;
}

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const navGroups = buildNavGroups(auth.roles ?? []);

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
                {navGroups.map((group) => (
                    <NavMain
                        key={group.label}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={[]} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
