import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DepartemenController from '@/actions/App/Http/Controllers/Master/DepartemenController';
import InputError from '@/components/input-error';
import { MasterNav } from '@/components/master-nav';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { index as departemenIndex } from '@/routes/master/departemen';
import type { Departemen, Paginated } from '@/types';

type PageProps = {
    departemens: Paginated<Departemen>;
    filters: { search: string };
};

export default function MasterDepartemen() {
    const { departemens, filters } = usePage<PageProps>().props;
    const [editing, setEditing] = useState<Departemen | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const { data, setData, post, put, processing, errors, reset } = useForm({
        nama_departemen: '',
        kode: '',
    });

    const startEdit = (departemen: Departemen) => {
        setEditing(departemen);
        setData({
            nama_departemen: departemen.nama_departemen,
            kode: departemen.kode,
        });
    };

    const cancelEdit = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            put(DepartemenController.update.url(editing.id), {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post(DepartemenController.store.url(), {
                onSuccess: () => reset(),
            });
        }
    };

    const destroy = (departemen: Departemen) => {
        if (confirm(`Hapus departemen "${departemen.nama_departemen}"?`)) {
            router.delete(DepartemenController.destroy.url(departemen.id));
        }
    };

    const runSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(departemenIndex.url(), { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Master Departemen" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Master Departemen</h1>

                <MasterNav />

                <Card className="max-w-lg">
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid grid-cols-1 items-end gap-3 sm:grid-cols-[1fr_120px_auto]"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="nama_departemen">
                                    Nama Departemen
                                </Label>
                                <Input
                                    id="nama_departemen"
                                    value={data.nama_departemen}
                                    onChange={(e) =>
                                        setData(
                                            'nama_departemen',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.nama_departemen} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="kode">Kode</Label>
                                <Input
                                    id="kode"
                                    value={data.kode}
                                    onChange={(e) =>
                                        setData('kode', e.target.value)
                                    }
                                />
                                <InputError message={errors.kode} />
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
                        placeholder="Cari departemen..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </form>

                <Card>
                    <CardContent className="divide-y p-0">
                        {departemens.data.map((departemen) => (
                            <div
                                key={departemen.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        {departemen.nama_departemen}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {departemen.kode} &middot;{' '}
                                        {departemen.karyawans_count ?? 0}{' '}
                                        karyawan
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(departemen)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => destroy(departemen)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={departemens.links} />
            </div>
        </>
    );
}

MasterDepartemen.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Master Departemen', href: departemenIndex() },
    ],
};
