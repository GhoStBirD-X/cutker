import logoFull from '@/images/logo-kkp-full.png';

export default function AppLogoFull({ className }: { className?: string }) {
    return <img src={logoFull} alt="KKP Inovasi" className={className} />;
}
