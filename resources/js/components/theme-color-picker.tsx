import { Check } from 'lucide-react';
import type { ColorTheme } from '@/hooks/use-theme';
import { useTheme } from '@/hooks/use-theme';
import { cn } from '@/lib/utils';

const THEMES: { value: ColorTheme; label: string; swatch: string }[] = [
    { value: 'default', label: 'Default', swatch: 'oklch(0.349 0.12 294.5)' },
    { value: 'retro', label: 'Retro Modern', swatch: 'oklch(0.58 0.16 40)' },
    { value: 'ocean', label: 'Ocean', swatch: 'oklch(0.52 0.14 235)' },
    { value: 'forest', label: 'Forest', swatch: 'oklch(0.48 0.12 148)' },
    { value: 'pixel', label: 'Pixel', swatch: 'oklch(0.45 0.09 130)' },
    {
        value: 'neubrutalism',
        label: 'Neubrutalism',
        swatch: 'oklch(0.8 0.19 95)',
    },
    { value: 'terminal', label: 'Terminal', swatch: 'oklch(0.45 0.13 150)' },
    { value: 'glass', label: 'Glass', swatch: 'oklch(0.55 0.18 280)' },
];

export function ThemeColorPicker() {
    const { theme, updateTheme } = useTheme();

    return (
        <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
            {THEMES.map(({ value, label, swatch }) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => updateTheme(value)}
                    className={cn(
                        'flex items-center gap-2 rounded-lg border p-2.5 text-sm transition-colors',
                        theme === value
                            ? 'border-primary bg-accent'
                            : 'border-input hover:bg-accent/50',
                    )}
                >
                    <span
                        className="size-5 shrink-0 rounded-full border border-black/10"
                        style={{ backgroundColor: swatch }}
                    />
                    <span className="flex-1 text-left">{label}</span>
                    {theme === value && (
                        <Check className="size-4 shrink-0 text-primary" />
                    )}
                </button>
            ))}
        </div>
    );
}
