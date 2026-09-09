import logoIcon from '@/images/logo-kkp-icon.png';

export default function AppLogoIcon({ className }: { className?: string }) {
    return <img src={logoIcon} alt="Logo KKP Inovasi" className={className} />;
}
