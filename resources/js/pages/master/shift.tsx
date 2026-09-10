import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ShiftController from '@/actions/App/Http/Controllers/Master/ShiftController';
import InputError from '@/components/input-error';
import { MasterNav } from '@/components/master-nav';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { index as shiftIndex } from '@/routes/master/shift';
import type { Paginated, Shift } from '@/types';

type PageProps = {
    shifts: Paginated<Shift>;
    filters: { search: string };
};

export default function MasterShift() {
    const { shifts, filters } = usePage<PageProps>().props;
    const [editing, setEditing] = useState<Shift | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const { data, setData, post, put, processing, errors, reset } = useForm({
        nama_shift: '',
        jam_mulai: '',
        jam_selesai: '',
    });

    const startEdit = (shift: Shift) => {
        setEditing(shift);
        setData({
            nama_shift: shift.nama_shift,
            jam_mulai: shift.jam_mulai,
            jam_selesai: shift.jam_selesai,
        });
    };

    const cancelEdit = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            put(ShiftController.update.url(editing.id), {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post(ShiftController.store.url(), { onSuccess: () => reset() });
        }
    };

    const destroy = (shift: Shift) => {
        if (confirm(`Hapus shift "${shift.nama_shift}"?`)) {
            router.delete(ShiftController.destroy.url(shift.id));
        }
    };

    const runSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(shiftIndex.url(), { search }, { preserveState: true });
    };

    return (
        <>
            <Head title="Master Shift" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Master Shift</h1>

                <MasterNav />

                <Card className="max-w-xl">
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_110px_110px_auto]"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="nama_shift">Nama Shift</Label>
                                <Input
                                    id="nama_shift"
                                    value={data.nama_shift}
                                    onChange={(e) =>
                                        setData('nama_shift', e.target.value)
                                    }
                                />
                                <InputError message={errors.nama_shift} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="jam_mulai">Jam Mulai</Label>
                                <Input
                                    id="jam_mulai"
                                    type="time"
                                    value={data.jam_mulai}
                                    onChange={(e) =>
                                        setData('jam_mulai', e.target.value)
                                    }
                                />
                                <InputError message={errors.jam_mulai} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="jam_selesai">Jam Selesai</Label>
                                <Input
                                    id="jam_selesai"
                                    type="time"
                                    value={data.jam_selesai}
                                    onChange={(e) =>
                                        setData('jam_selesai', e.target.value)
                                    }
                                />
                                <InputError message={errors.jam_selesai} />
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
                        placeholder="Cari shift..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </form>

                <Card>
                    <CardContent className="divide-y p-0">
                        {shifts.data.map((shift) => (
                            <div
                                key={shift.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="font-medium">
                                        {shift.nama_shift}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {shift.jam_mulai} - {shift.jam_selesai}
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(shift)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => destroy(shift)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={shifts.links} />
            </div>
        </>
    );
}

MasterShift.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Master Shift', href: shiftIndex() },
    ],
};
