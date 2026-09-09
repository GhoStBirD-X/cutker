import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center overflow-hidden rounded-lg bg-white p-1 ring-1 ring-black/5">
                <AppLogoIcon className="size-full object-contain" />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm">
                <span className="truncate leading-tight font-semibold">
                    {name}
                </span>
                <span className="truncate text-xs text-sidebar-foreground/60">
                    KKP Inovasi
                </span>
            </div>
        </>
    );
}
