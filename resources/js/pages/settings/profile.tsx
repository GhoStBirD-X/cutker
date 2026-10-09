import { Form, Head, router, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import {
    Briefcase,
    Building2,
    Camera,
    IdCard,
    Trash2,
    Users,
} from 'lucide-react';
import { useRef, useState } from 'react';
import type { ChangeEvent } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
    errors: Record<string, string>;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth, errors: pageErrors } = usePage<PageProps>().props;
    const getInitials = useInitials();
    const avatarInputRef = useRef<HTMLInputElement>(null);
    const [isAvatarProcessing, setIsAvatarProcessing] = useState(false);

    const unggahAvatar = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        event.target.value = '';

        if (!file) {
            return;
        }

        router.post(
            ProfileController.updateAvatar.url(),
            { avatar: file },
            {
                preserveScroll: true,
                onStart: () => setIsAvatarProcessing(true),
                onFinish: () => setIsAvatarProcessing(false),
            },
        );
    };

    const hapusAvatar = () => {
        router.delete(ProfileController.destroyAvatar.url(), {
            preserveScroll: true,
            onStart: () => setIsAvatarProcessing(true),
            onFinish: () => setIsAvatarProcessing(false),
        });
    };

    return (
        <>
            <Head title="Pengaturan profil" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profil"
                    description="Perbarui foto, nama, nomor HP, dan alamat email Anda"
                />

                <div className="flex flex-wrap items-center gap-4">
                    <Avatar className="size-20 overflow-hidden rounded-full">
                        <AvatarImage
                            src={auth.user.avatar ?? undefined}
                            alt={auth.user.name}
                            className="object-cover"
                        />
                        <AvatarFallback className="rounded-full bg-primary/10 text-2xl text-primary">
                            {getInitials(auth.user.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="grid gap-2">
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={isAvatarProcessing}
                                onClick={() => avatarInputRef.current?.click()}
                            >
                                <Camera />
                                {auth.user.avatar
                                    ? 'Ganti foto'
                                    : 'Unggah foto'}
                            </Button>
                            {auth.user.avatar && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    disabled={isAvatarProcessing}
                                    onClick={hapusAvatar}
                                >
                                    <Trash2 />
                                    Hapus
                                </Button>
                            )}
                        </div>
                        <p className="text-xs text-muted-foreground">
                            JPG, PNG, atau WEBP. Maksimal 2 MB.
                        </p>
                        <input
                            ref={avatarInputRef}
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            className="hidden"
                            onChange={unggahAvatar}
                            data-test="avatar-input"
                        />
                        <InputError message={pageErrors.avatar} />
                    </div>
                </div>

                {auth.user.karyawan && (
                    <div className="grid gap-4 rounded-lg border bg-muted/20 p-4 sm:grid-cols-2">
                        <ProfileField
                            icon={IdCard}
                            label="NPK"
                            value={auth.user.karyawan.nip}
                        />
                        <ProfileField
                            icon={Users}
                            label="Jenis Kelamin"
                            value={
                                auth.user.karyawan.jenis_kelamin === 'laki_laki'
                                    ? 'Laki-laki'
                                    : 'Perempuan'
                            }
                        />
                        <ProfileField
                            icon={Building2}
                            label="Departemen"
                            value={
                                auth.user.karyawan.departemen
                                    ?.nama_departemen ?? '-'
                            }
                        />
                        <ProfileField
                            icon={Briefcase}
                            label="Jabatan"
                            value={
                                auth.user.karyawan.jabatan?.nama_jabatan ?? '-'
                            }
                        />
                    </div>
                )}

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Nama lengkap"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Alamat email</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Alamat email"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {auth.user.karyawan && (
                                <div className="grid gap-2">
                                    <Label htmlFor="no_hp">
                                        Nomor HP (WhatsApp)
                                    </Label>

                                    <Input
                                        id="no_hp"
                                        type="tel"
                                        inputMode="tel"
                                        className="mt-1 block w-full"
                                        defaultValue={
                                            auth.user.karyawan.no_hp ?? ''
                                        }
                                        name="no_hp"
                                        autoComplete="tel"
                                        placeholder="08xxxxxxxxxx"
                                    />

                                    <p className="text-xs text-muted-foreground">
                                        Dipakai untuk notifikasi pengajuan cuti
                                        via WhatsApp.
                                    </p>

                                    <InputError
                                        className="mt-2"
                                        message={errors.no_hp}
                                    />
                                </div>
                            )}

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Alamat email Anda belum
                                            diverifikasi.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Klik di sini untuk mengirim
                                                ulang email verifikasi.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                Tautan verifikasi baru sudah
                                                dikirim ke email Anda.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

type ProfileFieldProps = {
    icon: React.ComponentType<{ className?: string }>;
    label: string;
    value: string;
};

function ProfileField({ icon: Icon, label, value }: ProfileFieldProps) {
    return (
        <div className="flex items-start gap-2.5">
            <Icon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
            <div className="grid gap-0.5">
                <Label className="text-muted-foreground">{label}</Label>
                <p className="text-sm font-medium">{value}</p>
            </div>
        </div>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan profil',
            href: edit(),
        },
    ],
};
