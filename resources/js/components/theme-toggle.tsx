import { Monitor, Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

const OPTIONS: { value: Appearance; icon: typeof Sun; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Terang' },
    { value: 'dark', icon: Moon, label: 'Gelap' },
    { value: 'system', icon: Monitor, label: 'Sistem' },
];

export function ThemeToggle() {
    const { appearance, resolvedAppearance, updateAppearance } =
        useAppearance();
    const CurrentIcon = resolvedAppearance === 'dark' ? Moon : Sun;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon">
                    <CurrentIcon className="size-5" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-40">
                {OPTIONS.map(({ value, icon: Icon, label }) => (
                    <DropdownMenuItem
                        key={value}
                        onClick={() => updateAppearance(value)}
                        className={cn(
                            appearance === value && 'font-medium text-primary',
                        )}
                    >
                        <Icon className="size-4" />
                        {label}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
