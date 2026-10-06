import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { ThemeColorPicker } from '@/components/theme-color-picker';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Pengaturan tampilan" />

            <h1 className="sr-only">Appearance settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Pengaturan tampilan"
                    description="Atur mode terang/gelap untuk akun Anda"
                />
                <AppearanceTabs />

                <Heading
                    variant="small"
                    title="Tema Warna"
                    description="Pilih palet warna yang kamu suka. Tetap bisa dikombinasikan dengan mode terang/gelap di atas."
                />
                <ThemeColorPicker />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan tampilan',
            href: editAppearance(),
        },
    ],
};
