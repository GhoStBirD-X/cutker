import { useSyncExternalStore } from 'react';

export type ColorTheme =
    | 'default'
    | 'retro'
    | 'ocean'
    | 'forest'
    | 'pixel'
    | 'neubrutalism'
    | 'terminal'
    | 'glass';

export type UseThemeReturn = {
    readonly theme: ColorTheme;
    readonly updateTheme: (theme: ColorTheme) => void;
};

const listeners = new Set<() => void>();
let currentTheme: ColorTheme = 'default';

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredTheme = (): ColorTheme => {
    if (typeof window === 'undefined') {
        return 'default';
    }

    return (localStorage.getItem('theme') as ColorTheme) || 'default';
};

// Orthogonal to the light/dark `.dark` class (see use-appearance.tsx):
// `data-theme` only swaps the color palette (--primary, --accent, etc.),
// so both axes combine freely (e.g. Ocean + dark). 'default' removes the
// attribute entirely so :root's original palette applies with no extra
// CSS block needed for it.
const applyTheme = (theme: ColorTheme): void => {
    if (typeof document === 'undefined') {
        return;
    }

    if (theme === 'default') {
        document.documentElement.removeAttribute('data-theme');
    } else {
        document.documentElement.setAttribute('data-theme', theme);
    }
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    currentTheme = getStoredTheme();
    applyTheme(currentTheme);
}

export function useTheme(): UseThemeReturn {
    const theme: ColorTheme = useSyncExternalStore(
        subscribe,
        () => currentTheme,
        () => 'default',
    );

    const updateTheme = (mode: ColorTheme): void => {
        currentTheme = mode;

        localStorage.setItem('theme', mode);
        setCookie('theme', mode);

        applyTheme(mode);
        notify();
    };

    return { theme, updateTheme } as const;
}
