import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import JenisCutiController from '@/actions/App/Http/Controllers/Master/JenisCutiController';
import InputError from '@/components/input-error';
import { MasterNav } from '@/components/master-nav';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { UnlimitedBadge } from '@/components/unlimited-badge';
import { dashboard } from '@/routes';
import { index as jenisCutiIndex } from '@/routes/master/jenis-cuti';
import type { JenisCuti, Paginated } from '@/types';

type PageProps = {
    jenisCutis: Paginated<JenisCuti>;
    filters: { search: string };
};

export default function MasterJenisCuti() {
    const { jenisCutis, filters } = usePage<PageProps>().props;
    const [editing, setEditing] = useState<JenisCuti | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const { data, setData, post, put, processing, errors, reset } = useForm({
        nama_jenis: '',
        kuota_default: null as number | null,
        masa_kerja_minimal_bulan: null as number | null,
        minimal_hari_pengajuan: null as number | null,
        khusus_gender: '' as '' | 'laki_laki' | 'perempuan',
        keterangan: '',
    });

    const startEdit = (jenis: JenisCuti) => {
        setEditing(jenis);
        setData({
            nama_jenis: jenis.nama_jenis,
            kuota_default: jenis.kuota_default,
            masa_kerja_minimal_bulan: jenis.masa_kerja_minimal_bulan,
            minimal_hari_pengajuan: jenis.minimal_hari_pengajuan,
            khusus_gender: jenis.khusus_gender ?? '',
            keterangan: jenis.keterangan ?? '',
        });
    };

    const cancelEdit = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            put(JenisCutiController.update.url(editing.id), {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post(JenisCutiController.store.url(), { onSuccess: () => reset() });
        }
    };

    const destroy = (jenis: JenisCuti) => {
        if (confirm(`Hapus jenis cuti "${jenis.nama_jenis}"?`)) {
            router.delete(JenisCutiController.destroy.url(jenis.id));
        }
    };

    const runSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(jenisCutiIndex.url(), { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Master Jenis Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Master Jenis Cuti</h1>

                <MasterNav />

                <Card className="max-w-4xl">
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="nama_jenis">Nama Jenis</Label>
                                <Input
                                    id="nama_jenis"
                                    value={data.nama_jenis}
                                    onChange={(e) =>
                                        setData('nama_jenis', e.target.value)
                                    }
                                />
                                <InputError message={errors.nama_jenis} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="kuota_default">
                                    Kuota (hari, kosongkan = tanpa batas)
                                </Label>
                                <Input
                                    id="kuota_default"
                                    type="number"
                                    min={0}
                                    value={data.kuota_default ?? ''}
                                    onChange={(e) =>
                                        setData(
                                            'kuota_default',
                                            e.target.value === ''
                                                ? null
                                                : Number(e.target.value),
                                        )
                                    }
                                />
                                <InputError message={errors.kuota_default} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="masa_kerja_minimal_bulan">
                                    Masa Kerja Min. (bulan)
                                </Label>
                                <Input
                                    id="masa_kerja_minimal_bulan"
                                    type="number"
                                    min={1}
                                    value={data.masa_kerja_minimal_bulan ?? ''}
                                    onChange={(e) =>
                                        setData(
                                            'masa_kerja_minimal_bulan',
                                            e.target.value === ''
                                                ? null
                                                : Number(e.target.value),
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.masa_kerja_minimal_bulan}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="minimal_hari_pengajuan">
                                    Min. Pengajuan (H-, kosongkan = tanpa
                                    batas)
                                </Label>
                                <Input
                                    id="minimal_hari_pengajuan"
                                    type="number"
                                    min={0}
                                    value={data.minimal_hari_pengajuan ?? ''}
                                    onChange={(e) =>
                                        setData(
                                            'minimal_hari_pengajuan',
                                            e.target.value === ''
                                                ? null
                                                : Number(e.target.value),
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.minimal_hari_pengajuan}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="khusus_gender">
                                    Khusus Gender
                                </Label>
                                <select
                                    id="khusus_gender"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={data.khusus_gender}
                                    onChange={(e) =>
                                        setData(
                                            'khusus_gender',
                                            e.target.value as
                                                '' | 'laki_laki' | 'perempuan',
                                        )
                                    }
                                >
                                    <option value="">Semua</option>
                                    <option value="laki_laki">Laki-laki</option>
                                    <option value="perempuan">Perempuan</option>
                                </select>
                                <InputError message={errors.khusus_gender} />
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
                        placeholder="Cari jenis cuti..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </form>

                <Card>
                    <CardContent className="divide-y p-0">
                        {jenisCutis.data.map((jenis) => (
                            <div
                                key={jenis.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        {jenis.nama_jenis}
                                    </div>
                                    <div className="flex flex-wrap items-center gap-1 text-muted-foreground">
                                        <span>
                                            Kuota{' '}
                                            {jenis.kuota_default !== null
                                                ? `${jenis.kuota_default} `
                                                : ''}
                                            hari
                                        </span>
                                        {jenis.kuota_default === null && (
                                            <UnlimitedBadge />
                                        )}
                                        {jenis.khusus_gender && (
                                            <span>
                                                ·{' '}
                                                {jenis.khusus_gender ===
                                                'perempuan'
                                                    ? 'Khusus Perempuan'
                                                    : 'Khusus Laki-laki'}
                                            </span>
                                        )}
                                        {(jenis.masa_kerja_minimal_bulan ||
                                            jenis.minimal_hari_pengajuan ||
                                            jenis.keterangan) && (
                                            <span>
                                                {jenis.masa_kerja_minimal_bulan
                                                    ? ` · Masa kerja min. ${jenis.masa_kerja_minimal_bulan} bulan`
                                                    : ''}
                                                {jenis.minimal_hari_pengajuan
                                                    ? ` · Min. H-${jenis.minimal_hari_pengajuan} pengajuan`
                                                    : ''}
                                                {jenis.keterangan
                                                    ? ` · ${jenis.keterangan}`
                                                    : ''}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(jenis)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => destroy(jenis)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={jenisCutis.links} />
            </div>
        </>
    );
}

MasterJenisCuti.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Master Jenis Cuti', href: jenisCutiIndex() },
    ],
};
