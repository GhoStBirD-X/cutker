import logoFullDark from '@/images/logo-kkp-full-dark.png';
import logoFull from '@/images/logo-kkp-full.png';
import { cn } from '@/lib/utils';

export default function AppLogoFull({ className }: { className?: string }) {
    return (
        <>
            <img
                src={logoFull}
                alt="KKP Inovasi"
                className={cn(className, 'dark:hidden')}
            />
            <img
                src={logoFullDark}
                alt="KKP Inovasi"
                className={cn(className, 'hidden dark:block')}
            />
        </>
    );
}
