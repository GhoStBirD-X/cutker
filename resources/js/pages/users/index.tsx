import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/UserManagement/UserController';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

    const destroy = (user: UserRow) => {
        if (confirm(`Hapus user "${user.name}"?`)) {
            router.delete(UserController.destroy.url(user.id));
        }
    };

    const runSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(usersIndex.url(), { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Kelola User" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Kelola User & Role</h1>

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
                                            {role}
                                        </label>
                                    ))}
                                </div>
                                <InputError message={errors.roles} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="karyawan_id">
                                    Hubungkan ke Karyawan (opsional)
                                </Label>
                                <select
                                    id="karyawan_id"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
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
                                </select>
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

                <Card>
                    <CardContent className="divide-y p-0">
                        {users.data.map((user) => (
                            <div
                                key={user.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="flex flex-wrap items-center gap-2 font-medium">
                                        {user.name}
                                        {user.roles.map((role) => (
                                            <Badge
                                                key={role.name}
                                                variant="secondary"
                                            >
                                                {role.name}
                                            </Badge>
                                        ))}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {user.email}
                                        {user.karyawan
                                            ? ` · terhubung ke ${user.karyawan.nama}`
                                            : ''}
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(user)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => destroy(user)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={users.links} />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Kelola User', href: usersIndex() },
    ],
};
