import { Head } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { login } from '@/routes';

export default function ForgotPassword() {
    return (
        <>
            <Head title="Lupa password" />

            <div className="space-y-6">
                <div className="space-y-2 rounded-lg border bg-muted/40 p-4 text-sm">
                    <p>
                        Hubungi admin atau HR untuk mereset password akun Anda.
                    </p>
                    <p className="text-muted-foreground">
                        Setelah direset, masuk dengan NPK Anda sebagai username
                        dan password, lalu segera ganti password di menu
                        Pengaturan.
                    </p>
                </div>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>Kembali ke halaman</span>
                    <TextLink href={login()}>masuk</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Lupa password',
    description: 'Reset password dilakukan oleh admin/HR',
};
