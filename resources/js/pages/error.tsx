import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoFull from '@/components/app-logo-full';
import { Button } from '@/components/ui/button';
import { dashboard, home } from '@/routes';
import type { Auth } from '@/types';

const PESAN: Record<number, { judul: string; deskripsi: string }> = {
    403: {
        judul: 'Akses ditolak',
        deskripsi:
            'Anda tidak memiliki izin untuk membuka halaman ini. Hubungi HRD atau admin jika menurut Anda ini keliru.',
    },
    404: {
        judul: 'Halaman tidak ditemukan',
        deskripsi:
            'Halaman yang Anda cari tidak ada atau sudah dipindahkan. Periksa kembali alamatnya.',
    },
    500: {
        judul: 'Terjadi kesalahan pada server',
        deskripsi:
            'Maaf, ada masalah di sistem kami. Silakan coba lagi beberapa saat lagi.',
    },
    503: {
        judul: 'Sedang dalam pemeliharaan',
        deskripsi:
            'Sistem sedang diperbarui. Silakan kembali beberapa saat lagi.',
    },
};

export default function ErrorPage({ status }: { status: number }) {
    const { auth } = usePage<{ auth?: Auth }>().props;
    const pesan = PESAN[status] ?? PESAN[500];

    return (
        <>
            <Head title={pesan.judul} />
            <div className="flex min-h-screen flex-col items-center justify-center gap-6 bg-background px-4 text-center text-foreground">
                <AppLogoFull className="h-8 w-auto object-contain" />
                <div>
                    <p className="text-5xl font-bold text-primary">{status}</p>
                    <h1 className="mt-2 text-xl font-semibold">
                        {pesan.judul}
                    </h1>
                    <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                        {pesan.deskripsi}
                    </p>
                </div>
                <div className="flex flex-wrap justify-center gap-2">
                    <Button variant="outline" onClick={() => history.back()}>
                        Kembali
                    </Button>
                    <Button asChild>
                        <Link href={auth?.user ? dashboard() : home()}>
                            {auth?.user ? 'Ke Dashboard' : 'Ke Beranda'}
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}
