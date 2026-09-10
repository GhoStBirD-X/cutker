import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import HariLiburController from '@/actions/App/Http/Controllers/Master/HariLiburController';
import InputError from '@/components/input-error';
import { MasterNav } from '@/components/master-nav';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as hariLiburIndex } from '@/routes/master/hari-libur';
import type { HariLibur, Paginated } from '@/types';

type PageProps = {
    hariLiburs: Paginated<HariLibur>;
    filters: { search: string };
};

export default function MasterHariLibur() {
    const { hariLiburs, filters } = usePage<PageProps>().props;
    const [editing, setEditing] = useState<HariLibur | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const { data, setData, post, put, processing, errors, reset } = useForm({
        tanggal: '',
        keterangan: '',
    });

    const startEdit = (hariLibur: HariLibur) => {
        setEditing(hariLibur);
        setData({
            tanggal: hariLibur.tanggal.slice(0, 10),
            keterangan: hariLibur.keterangan,
        });
    };

    const cancelEdit = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            put(HariLiburController.update.url(editing.id), {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post(HariLiburController.store.url(), {
                onSuccess: () => reset(),
            });
        }
    };

    const destroy = (hariLibur: HariLibur) => {
        if (confirm(`Hapus hari libur "${hariLibur.keterangan}"?`)) {
            router.delete(HariLiburController.destroy.url(hariLibur.id));
        }
    };

    const runSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(hariLiburIndex.url(), { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Master Hari Libur" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Master Hari Libur</h1>
                <p className="text-sm text-muted-foreground">
                    Hari libur nasional disinkronkan otomatis dari kalender
                    Indonesia setiap awal tahun. Tambahkan di sini untuk
                    melengkapi dengan cuti bersama khusus perusahaan, atau
                    mengoreksi tanggal yang terlewat sinkronisasi. Cuti yang
                    bertabrakan dengan tanggal di sini otomatis mengurangi
                    jumlah hari yang memotong saldo.
                </p>

                <MasterNav />

                <Card className="max-w-2xl">
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid grid-cols-1 items-end gap-3 sm:grid-cols-[160px_1fr_auto]"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="tanggal">Tanggal</Label>
                                <Input
                                    id="tanggal"
                                    type="date"
                                    value={data.tanggal}
                                    onChange={(e) =>
                                        setData('tanggal', e.target.value)
                                    }
                                />
                                <InputError message={errors.tanggal} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="keterangan">Keterangan</Label>
                                <Input
                                    id="keterangan"
                                    value={data.keterangan}
                                    onChange={(e) =>
                                        setData('keterangan', e.target.value)
                                    }
                                />
                                <InputError message={errors.keterangan} />
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
                        placeholder="Cari keterangan..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </form>

                <Card>
                    <CardContent className="divide-y p-0">
                        {hariLiburs.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">
                                Belum ada data hari libur.
                            </p>
                        )}
                        {hariLiburs.data.map((hariLibur) => (
                            <div
                                key={hariLibur.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="flex flex-wrap items-center gap-2 font-medium">
                                        {formatDate(hariLibur.tanggal)}
                                        <Badge
                                            variant={
                                                hariLibur.sumber === 'nasional'
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                        >
                                            {hariLibur.sumber === 'nasional'
                                                ? 'Nasional'
                                                : 'Perusahaan'}
                                        </Badge>
                                    </div>
                                    <div className="text-muted-foreground">
                                        {hariLibur.keterangan}
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(hariLibur)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => destroy(hariLibur)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={hariLiburs.links} />
            </div>
        </>
    );
}

MasterHariLibur.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Master Hari Libur', href: hariLiburIndex() },
    ],
};
