import { Head, router, useForm, usePage } from '@inertiajs/react';
import { KeyRound, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/UserManagement/UserController';
import { konfirmasi } from '@/components/confirm-dialog';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { roleLabel } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as usersIndex } from '@/routes/users';
import type { Paginated, Role, User } from '@/types';

type UserRow = User & { roles: { name: Role }[] };

type PageProps = {
    users: Paginated<UserRow>;
    karyawans: { id: number; nama: string; nip: string }[];
    filters: { search: string };
};

const ROLES: Role[] = [
    'karyawan',
    'kepala_bagian',
    'koordinator_shift',
    'hrd',
    'manager',
    'admin',
];

export default function UsersIndex() {
    const { users, karyawans, filters } = usePage<PageProps>().props;
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [dipilih, setDipilih] = useState<Set<number>>(new Set());
    const [mereset, setMereset] = useState(false);

    const bisaDireset = users.data.filter((u) => u.karyawan?.nip);
    const semuaHalamanDipilih =
        bisaDireset.length > 0 && bisaDireset.every((u) => dipilih.has(u.id));

    const togglePilih = (id: number) => {
        setDipilih((prev) => {
            const next = new Set(prev);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    };

    const togglePilihHalaman = () => {
        setDipilih(
            semuaHalamanDipilih
                ? new Set()
                : new Set(bisaDireset.map((u) => u.id)),
        );
    };

    const resetPasswordKeNpk = async (target: UserRow[]) => {
        if (
            !(await konfirmasi({
                title:
                    target.length === 1
                        ? `Reset password ${target[0].name} ke NPK?`
                        : `Reset password ${target.length} user ke NPK?`,
                description:
                    'Password diganti menjadi NPK karyawan masing-masing, sehingga bisa login dengan NPK sebagai password. Minta mereka segera mengganti password setelah login.',
                confirmText: 'Reset ke NPK',
                destructive: true,
            }))
        ) {
            return;
        }

        router.post(
            UserController.resetPasswordKeNpk.url(),
            { user_ids: target.map((u) => u.id) },
            {
                preserveScroll: true,
                onStart: () => setMereset(true),
                onFinish: () => setMereset(false),
                onSuccess: () => setDipilih(new Set()),
            },
        );
    };

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        roles: ['karyawan'] as Role[],
        karyawan_id: '',
    });

    const startEdit = (user: UserRow) => {
        setEditing(user);
        setData({
            name: user.name,
            email: user.email,
            password: '',
            roles: user.roles.map((r) => r.name),
            karyawan_id: user.karyawan_id ? String(user.karyawan_id) : '',
        });
    };

    const toggleRole = (role: Role) => {
        setData(
            'roles',
            data.roles.includes(role)
                ? data.roles.filter((r) => r !== role)
                : [...data.roles, role],
        );
    };

    const cancelEdit = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            put(UserController.update.url(editing.id), {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post(UserController.store.url(), { onSuccess: () => reset() });
        }
    };

    const destroy = async (user: UserRow) => {
        if (
            await konfirmasi({
                title: `Hapus user "${user.name}"?`,
                description: 'Data yang dihapus tidak bisa dikembalikan.',
                confirmText: 'Hapus',
                destructive: true,
            })
        ) {
            router.delete(UserController.destroy.url(user.id));
        }
    };

    const runSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(usersIndex.url(), { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="User & Role" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <ShieldCheck className="size-5 text-primary" />
                    Kelola User & Role
                </h1>

                <Card className="max-w-3xl">
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                />
                                <InputError message={errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    Password{' '}
                                    {editing && '(kosongkan jika tidak diubah)'}
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Role (bisa lebih dari satu)</Label>
                                <div className="flex flex-wrap gap-3 rounded-md border border-input p-2">
                                    {ROLES.map((role) => (
                                        <label
                                            key={role}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={data.roles.includes(
                                                    role,
                                                )}
                                                onCheckedChange={() =>
                                                    toggleRole(role)
                                                }
                                            />
                                            {roleLabel(role)}
                                        </label>
                                    ))}
                                </div>
                                <InputError message={errors.roles} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="karyawan_id">
                                    Hubungkan ke Karyawan (opsional)
                                </Label>
                                <NativeSelect
                                    id="karyawan_id"

                                    value={data.karyawan_id}
                                    onChange={(e) =>
                                        setData('karyawan_id', e.target.value)
                                    }
                                >
                                    <option value="">- Tidak ada -</option>
                                    {karyawans.map((k) => (
                                        <option key={k.id} value={k.id}>
                                            {k.nama} ({k.nip})
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={errors.karyawan_id} />
                            </div>
                            <div className="flex items-end gap-2">
                                <Button type="submit" disabled={processing}>
                                    {editing ? 'Simpan' : 'Tambah User'}
                                </Button>
                                {editing && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={cancelEdit}
                                    >
                                        Batal
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <form onSubmit={runSearch} className="max-w-sm">
                    <Input
                        placeholder="Cari user..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </form>

                <Card className="gap-0 overflow-hidden py-0">
                    <div className="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/40 px-4 py-2 text-sm">
                        <label className="flex items-center gap-2">
                            <Checkbox
                                checked={semuaHalamanDipilih}
                                disabled={bisaDireset.length === 0}
                                onCheckedChange={togglePilihHalaman}
                            />
                            {dipilih.size > 0
                                ? `${dipilih.size} user dipilih`
                                : 'Pilih semua di halaman ini'}
                        </label>
                        {dipilih.size > 0 && (
                            <Button
                                size="sm"
                                variant="outline"
                                className="gap-1.5"
                                disabled={mereset}
                                onClick={() =>
                                    resetPasswordKeNpk(
                                        users.data.filter((u) =>
                                            dipilih.has(u.id),
                                        ),
                                    )
                                }
                            >
                                <KeyRound className="size-4" />
                                Reset Password ke NPK ({dipilih.size})
                            </Button>
                        )}
                    </div>
                    <CardContent className="divide-y p-0">
                        {users.data.map((user) => (
                            <div
                                key={user.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="flex items-start gap-3">
                                    <Checkbox
                                        className="mt-0.5"
                                        aria-label={`Pilih ${user.name}`}
                                        checked={dipilih.has(user.id)}
                                        disabled={!user.karyawan?.nip}
                                        onCheckedChange={() =>
                                            togglePilih(user.id)
                                        }
                                    />
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2 font-medium">
                                            {user.name}
                                            {user.roles.map((role) => (
                                                <Badge
                                                    key={role.name}
                                                    variant="secondary"
                                                >
                                                    {roleLabel(role.name)}
                                                </Badge>
                                            ))}
                                        </div>
                                        <div className="text-muted-foreground">
                                            {user.email}
                                            {user.karyawan
                                                ? ` · terhubung ke ${user.karyawan.nama} (NPK ${user.karyawan.nip})`
                                                : ''}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    {user.karyawan?.nip && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            disabled={mereset}
                                            onClick={() =>
                                                resetPasswordKeNpk([user])
                                            }
                                        >
                                            Reset ke NPK
                                        </Button>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(user)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        onClick={() => destroy(user)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={users.links} perPage={users.per_page} />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'User & Role', href: usersIndex() },
    ],
};
