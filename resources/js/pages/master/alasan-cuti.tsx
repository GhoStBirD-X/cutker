import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AlasanCutiController from '@/actions/App/Http/Controllers/Master/AlasanCutiController';
import InputError from '@/components/input-error';
import { MasterNav } from '@/components/master-nav';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { UnlimitedBadge } from '@/components/unlimited-badge';
import { dashboard } from '@/routes';
import { index as alasanCutiIndex } from '@/routes/master/alasan-cuti';
import type { AlasanCuti, JenisCuti, Paginated } from '@/types';

type PageProps = {
    alasanCutis: Paginated<AlasanCuti>;
    jenisCutis: JenisCuti[];
    filters: { search: string };
};

export default function MasterAlasanCuti() {
    const { alasanCutis, jenisCutis, filters } = usePage<PageProps>().props;
    const [editing, setEditing] = useState<AlasanCuti | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const { data, setData, post, put, processing, errors, reset } = useForm({
        jenis_cuti_id: '',
        nama_alasan: '',
        jumlah_hari: null as number | null,
        keterangan: '',
    });

    const startEdit = (alasan: AlasanCuti) => {
        setEditing(alasan);
        setData({
            jenis_cuti_id: String(alasan.jenis_cuti_id),
            nama_alasan: alasan.nama_alasan,
            jumlah_hari: alasan.jumlah_hari,
            keterangan: alasan.keterangan ?? '',
        });
    };

    const cancelEdit = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            put(AlasanCutiController.update.url(editing.id), {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post(AlasanCutiController.store.url(), {
                onSuccess: () => reset(),
            });
        }
    };

    const destroy = (alasan: AlasanCuti) => {
        if (confirm(`Hapus alasan cuti "${alasan.nama_alasan}"?`)) {
            router.delete(AlasanCutiController.destroy.url(alasan.id));
        }
    };

    const runSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(alasanCutiIndex.url(), { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Master Alasan Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Master Alasan Cuti</h1>

                <MasterNav />

                <Card className="max-w-4xl">
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="jenis_cuti_id">
                                    Jenis Cuti
                                </Label>
                                <Select
                                    name="jenis_cuti_id"
                                    value={data.jenis_cuti_id}
                                    onValueChange={(value) =>
                                        setData('jenis_cuti_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="jenis_cuti_id"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Pilih jenis cuti" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {jenisCutis.map((jenis) => (
                                            <SelectItem
                                                key={jenis.id}
                                                value={String(jenis.id)}
                                            >
                                                {jenis.nama_jenis}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.jenis_cuti_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="nama_alasan">Nama Alasan</Label>
                                <Input
                                    id="nama_alasan"
                                    value={data.nama_alasan}
                                    onChange={(e) =>
                                        setData('nama_alasan', e.target.value)
                                    }
                                />
                                <InputError message={errors.nama_alasan} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="jumlah_hari">
                                    Hari (kosong = tanpa batas)
                                </Label>
                                <Input
                                    id="jumlah_hari"
                                    type="number"
                                    min={1}
                                    value={data.jumlah_hari ?? ''}
                                    onChange={(e) =>
                                        setData(
                                            'jumlah_hari',
                                            e.target.value === ''
                                                ? null
                                                : Number(e.target.value),
                                        )
                                    }
                                />
                                <InputError message={errors.jumlah_hari} />
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
                            <div className="grid gap-2">
                                <Label className="invisible">Aksi</Label>
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
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <form onSubmit={runSearch} className="max-w-sm">
                    <Input
                        placeholder="Cari alasan cuti..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </form>

                <Card>
                    <CardContent className="divide-y p-0">
                        {alasanCutis.data.map((alasan) => (
                            <div
                                key={alasan.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        {alasan.nama_alasan}
                                    </div>
                                    <div className="flex flex-wrap items-center gap-1 text-muted-foreground">
                                        <span>
                                            {alasan.jenis_cuti?.nama_jenis} ·{' '}
                                            {alasan.jumlah_hari === null
                                                ? 'hari'
                                                : `Maks ${alasan.jumlah_hari} hari`}
                                        </span>
                                        {alasan.jumlah_hari === null && (
                                            <UnlimitedBadge />
                                        )}
                                        {alasan.keterangan && (
                                            <span>· {alasan.keterangan}</span>
                                        )}
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(alasan)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => destroy(alasan)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={alasanCutis.links} />
            </div>
        </>
    );
}

MasterAlasanCuti.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Master Alasan Cuti', href: alasanCutiIndex() },
    ],
};
