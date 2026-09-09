import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarCheck, ClipboardCheck, Users } from 'lucide-react';
import AppLogoFull from '@/components/app-logo-full';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard, login } from '@/routes';

const FEATURES = [
    {
        icon: ClipboardCheck,
        title: 'Pengajuan & Approval Cepat',
        description:
            'Ajukan cuti dan pantau persetujuan berjenjang dari Kepala Bagian, HRD, hingga Manager secara real-time.',
    },
    {
        icon: CalendarCheck,
        title: 'Saldo & Jadwal Otomatis',
        description:
            'Saldo cuti tahunan dan jadwal shift terkelola otomatis, tanpa hitung manual.',
    },
    {
        icon: Users,
        title: 'Untuk Seluruh Karyawan',
        description:
            'Satu sistem untuk karyawan, Kepala Bagian, HRD, Manager, dan admin — sesuai peran masing-masing.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Sistem Cuti Kerja Pabrik" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="flex items-center justify-between px-6 py-5 sm:px-10">
                    <AppLogoFull className="h-8 w-auto object-contain sm:h-9" />
                    <div className="flex items-center gap-2">
                        <ThemeToggle />
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>Ke Dashboard</Link>
                            </Button>
                        ) : (
                            <Button asChild>
                                <Link href={login()}>Masuk</Link>
                            </Button>
                        )}
                    </div>
                </header>

                <main className="flex flex-1 flex-col items-center justify-center gap-16 px-6 py-16 sm:px-10">
                    <div className="max-w-2xl text-center">
                        <h1 className="text-3xl font-semibold tracking-tight text-balance sm:text-5xl">
                            Sistem Manajemen{' '}
                            <span className="text-primary">
                                Cuti Kerja Pabrik
                            </span>
                        </h1>
                        <p className="mt-4 text-base text-muted-foreground sm:text-lg">
                            Kelola pengajuan cuti, approval berjenjang, saldo,
                            dan jadwal shift karyawan pabrik dalam satu sistem
                            yang mudah dipakai dari HP maupun komputer.
                        </p>
                        <div className="mt-8 flex justify-center gap-3">
                            <Button asChild size="lg">
                                <Link href={auth.user ? dashboard() : login()}>
                                    {auth.user
                                        ? 'Ke Dashboard'
                                        : 'Masuk ke Sistem'}
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div className="grid w-full max-w-4xl gap-4 sm:grid-cols-3">
                        {FEATURES.map((feature) => (
                            <Card key={feature.title}>
                                <CardContent className="flex flex-col items-start gap-3 pt-2">
                                    <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <feature.icon className="size-5" />
                                    </div>
                                    <h2 className="font-semibold">
                                        {feature.title}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {feature.description}
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </main>

                <footer className="px-6 py-6 text-center text-xs text-muted-foreground sm:px-10">
                    &copy; {new Date().getFullYear()} KKP Inovasi &mdash; Sistem
                    Cuti Kerja Pabrik
                </footer>
            </div>
        </>
    );
}
