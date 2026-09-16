import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import JabatanController from '@/actions/App/Http/Controllers/Master/JabatanController';
import InputError from '@/components/input-error';
import { MasterNav } from '@/components/master-nav';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { index as jabatanIndex } from '@/routes/master/jabatan';
import type { Jabatan, Paginated } from '@/types';

type PageProps = {
    jabatans: Paginated<Jabatan>;
    filters: { search: string };
};

export default function MasterJabatan() {
    const { jabatans, filters } = usePage<PageProps>().props;
    const [editing, setEditing] = useState<Jabatan | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const { data, setData, post, put, processing, errors, reset } = useForm({
        nama_jabatan: '',
    });

    const startEdit = (jabatan: Jabatan) => {
        setEditing(jabatan);
        setData({ nama_jabatan: jabatan.nama_jabatan });
    };

    const cancelEdit = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            put(JabatanController.update.url(editing.id), {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post(JabatanController.store.url(), { onSuccess: () => reset() });
        }
    };

    const destroy = (jabatan: Jabatan) => {
        if (confirm(`Hapus jabatan "${jabatan.nama_jabatan}"?`)) {
            router.delete(JabatanController.destroy.url(jabatan.id));
        }
    };

    const runSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(jabatanIndex.url(), { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Master Jabatan" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Master Jabatan</h1>

                <MasterNav />

                <Card className="max-w-lg">
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid grid-cols-1 items-end gap-3 sm:grid-cols-[1fr_auto]"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="nama_jabatan">
                                    Nama Jabatan
                                </Label>
                                <Input
                                    id="nama_jabatan"
                                    value={data.nama_jabatan}
                                    onChange={(e) =>
                                        setData('nama_jabatan', e.target.value)
                                    }
                                />
                                <InputError message={errors.nama_jabatan} />
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    {editing ? 'Simpan' : 'Tambah'}
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
                        placeholder="Cari jabatan..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </form>

                <Card>
                    <CardContent className="divide-y p-0">
                        {jabatans.data.map((jabatan) => (
                            <div
                                key={jabatan.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        {jabatan.nama_jabatan}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {jabatan.karyawans_count ?? 0} karyawan
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(jabatan)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => destroy(jabatan)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={jabatans.links} perPage={jabatans.per_page} />
            </div>
        </>
    );
}

MasterJabatan.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Master Jabatan', href: jabatanIndex() },
    ],
};
