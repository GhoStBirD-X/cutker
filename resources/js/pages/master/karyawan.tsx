import { Head, router, useForm, usePage } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { useEffect, useState } from 'react';
import KaryawanController from '@/actions/App/Http/Controllers/Master/KaryawanController';
import InputError from '@/components/input-error';
import { MasterNav } from '@/components/master-nav';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { index as karyawanIndex } from '@/routes/master/karyawan';
import type { Auth, Departemen, Jabatan, Karyawan, Paginated } from '@/types';

type KaryawanRow = Karyawan & {
    departemen?: Departemen;
    jabatan?: Jabatan;
    user?: { roles: { name: string }[] } | null;
};

type PageProps = {
    auth: Auth;
    karyawans: Paginated<KaryawanRow>;
    departemens: Departemen[];
    jabatans: Jabatan[];
    kepalaBagianPerDepartemen: {
        departemen: string;
        kepala_bagian: string | null;
    }[];
    filters: { search: string; departemen_id: number | null };
};

const FRASA_KONFIRMASI_RESET = 'HAPUS SEMUA KARYAWAN';

type ImportFailure = { row: number; errors: string[] };
type ImportKredensial = {
    nip: string;
    nama: string;
    email: string;
    password: string;
    role: string;
};

const emptyForm = {
    nip: '',
    nama: '',
    email: '',
    no_hp: '',
    jenis_kelamin: 'laki_laki',
    departemen_id: '',
    jabatan_id: '',
    tanggal_masuk: '',
    status: 'aktif',
    tipe_karyawan: 'tetap',
    tanggal_akhir_kontrak: '',
    buat_akun: false,
    akun_password: '',
    akun_role: 'karyawan',
};

const OPSI_ROLE = [
    'karyawan',
    'kepala_bagian',
    'koordinator_shift',
    'hrd',
    'manager',
    'admin',
];

export default function MasterKaryawan() {
    const {
        auth,
        karyawans,
        departemens,
        jabatans,
        kepalaBagianPerDepartemen,
        filters,
    } = usePage<PageProps>().props;
    const [editing, setEditing] = useState<KaryawanRow | null>(null);
    const [konfirmasiReset, setKonfirmasiReset] = useState('');
    const [resetProcessing, setResetProcessing] = useState(false);
    const [search, setSearch] = useState(filters.search ?? '');
    const [departemenFilter, setDepartemenFilter] = useState(
        filters.departemen_id ? String(filters.departemen_id) : '',
    );

    const { data, setData, post, put, processing, errors, reset } =
        useForm(emptyForm);

    const [importFailures, setImportFailures] = useState<ImportFailure[]>([]);
    const [importKredensial, setImportKredensial] = useState<
        ImportKredensial[]
    >([]);
    const importForm = useForm<{ file: File | null }>({ file: null });

    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash as
                | {
                      importFailures?: ImportFailure[];
                      importKredensial?: ImportKredensial[];
                  }
                | undefined;

            if (flash?.importFailures) {
                setImportFailures(flash.importFailures);
            }

            if (flash?.importKredensial) {
                setImportKredensial(flash.importKredensial);
            }
        });
    }, []);

    const unduhKredensial = () => {
        const header = 'NIP,Nama,Email,Password,Role';
        const baris = importKredensial.map((k) =>
            [k.nip, k.nama, k.email, k.password, k.role]
                .map((nilai) => `"${nilai.replace(/"/g, '""')}"`)
                .join(','),
        );
        const csv = [header, ...baris].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `kredensial-karyawan-${new Date().toISOString().slice(0, 10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    };

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();

        if (!importForm.data.file) {
            return;
        }

        setImportFailures([]);
        setImportKredensial([]);
        importForm.post(KaryawanController.importMethod.url(), {
            forceFormData: true,
            onSuccess: () => importForm.reset(),
        });
    };

    const startEdit = (karyawan: KaryawanRow) => {
        setEditing(karyawan);
        setData({
            nip: karyawan.nip,
            nama: karyawan.nama,
            email: karyawan.email,
            no_hp: karyawan.no_hp ?? '',
            jenis_kelamin: karyawan.jenis_kelamin,
            departemen_id: String(karyawan.departemen_id),
            jabatan_id: String(karyawan.jabatan_id),
            tanggal_masuk: karyawan.tanggal_masuk.slice(0, 10),
            status: karyawan.status,
            tipe_karyawan: karyawan.tipe_karyawan,
            tanggal_akhir_kontrak:
                karyawan.tanggal_akhir_kontrak?.slice(0, 10) ?? '',
            buat_akun: false,
            akun_password: '',
            akun_role: 'karyawan',
        });
    };

    const cancelEdit = () => {
        setEditing(null);
        reset();
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editing) {
            put(KaryawanController.update.url(editing.id), {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post(KaryawanController.store.url(), { onSuccess: () => reset() });
        }
    };

    const destroy = (karyawan: KaryawanRow) => {
        if (confirm(`Hapus karyawan "${karyawan.nama}"?`)) {
            router.delete(KaryawanController.destroy.url(karyawan.id));
        }
    };

    const submitResetData = (e: React.FormEvent) => {
        e.preventDefault();
        setResetProcessing(true);
        router.post(
            KaryawanController.resetData.url(),
            { konfirmasi: konfirmasiReset },
            {
                onSuccess: () => setKonfirmasiReset(''),
                onFinish: () => setResetProcessing(false),
            },
        );
    };

    const runFilter = (e?: React.FormEvent) => {
        e?.preventDefault();
        router.get(
            karyawanIndex.url(),
            { search, departemen_id: departemenFilter || undefined },
            { preserveState: true },
        );
    };

    return (
        <>
            <Head title="Master Karyawan" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Master Karyawan</h1>

                <MasterNav />

                <Card>
                    <CardContent>
                        <h2 className="mb-2 text-sm font-semibold text-muted-foreground">
                            Kepala Bagian per Departemen
                        </h2>
                        <p className="mb-3 text-xs text-muted-foreground">
                            Pengajuan cuti karyawan otomatis dirutekan ke Kepala
                            Bagian departemen tempat mereka ditempatkan
                            (approval level 1) sebelum diteruskan ke HRD lalu
                            Manager. Pastikan setiap departemen punya satu
                            karyawan dengan role &quot;kepala_bagian&quot; (atur
                            lewat Kelola User).
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {kepalaBagianPerDepartemen.map((item) => (
                                <Badge
                                    key={item.departemen}
                                    variant={
                                        item.kepala_bagian
                                            ? 'secondary'
                                            : 'destructive'
                                    }
                                >
                                    {item.departemen}:{' '}
                                    {item.kepala_bagian ??
                                        'belum ada kepala bagian'}
                                </Badge>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent>
                        <h2 className="mb-2 text-sm font-semibold text-muted-foreground">
                            Import Data Karyawan (Excel/CSV)
                        </h2>
                        <p className="mb-3 text-xs text-muted-foreground">
                            Untuk memasukkan data karyawan pabrik dalam jumlah
                            besar. Departemen dan jabatan pada file harus sudah
                            ada di Master Departemen/Jabatan. Karyawan yang
                            gagal diimpor tidak akan menghentikan baris lain
                            yang valid.
                        </p>
                        <form
                            onSubmit={submitImport}
                            className="flex flex-wrap items-end gap-2"
                        >
                            <a
                                href={KaryawanController.importTemplate.url()}
                                className="text-sm text-primary underline underline-offset-4"
                            >
                                Unduh Template
                            </a>
                            <Input
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                onChange={(e) =>
                                    importForm.setData(
                                        'file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                className="max-w-xs"
                            />
                            <Button
                                type="submit"
                                disabled={
                                    importForm.processing ||
                                    !importForm.data.file
                                }
                            >
                                Import
                            </Button>
                            <InputError message={importForm.errors.file} />
                        </form>

                        {importFailures.length > 0 && (
                            <div className="mt-4 rounded-md border border-destructive/50 p-3">
                                <h3 className="mb-2 text-sm font-semibold text-destructive">
                                    {importFailures.length} baris gagal diimpor
                                </h3>
                                <ul className="space-y-1 text-xs text-muted-foreground">
                                    {importFailures.map((failure) => (
                                        <li key={failure.row}>
                                            <span className="font-medium">
                                                Baris {failure.row}:
                                            </span>{' '}
                                            {failure.errors.join(' ')}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {importKredensial.length > 0 && (
                            <div className="mt-4 rounded-md border border-blue-200 p-3 dark:border-blue-900">
                                <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                                    <h3 className="text-sm font-semibold">
                                        Akun login untuk{' '}
                                        {importKredensial.length} karyawan baru
                                    </h3>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={unduhKredensial}
                                    >
                                        Unduh sebagai CSV
                                    </Button>
                                </div>
                                <p className="mb-2 text-xs text-muted-foreground">
                                    Password hanya ditampilkan sekali di sini —
                                    segera unduh/catat dan bagikan ke
                                    masing-masing karyawan. Meninggalkan halaman
                                    ini akan menghilangkan daftar ini secara
                                    permanen.
                                </p>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="border-b text-muted-foreground">
                                                <th className="py-1 pr-3">
                                                    NIP
                                                </th>
                                                <th className="py-1 pr-3">
                                                    Nama
                                                </th>
                                                <th className="py-1 pr-3">
                                                    Email
                                                </th>
                                                <th className="py-1 pr-3">
                                                    Password
                                                </th>
                                                <th className="py-1">Role</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {importKredensial.map((k) => (
                                                <tr key={k.email}>
                                                    <td className="py-1 pr-3">
                                                        {k.nip}
                                                    </td>
                                                    <td className="py-1 pr-3">
                                                        {k.nama}
                                                    </td>
                                                    <td className="py-1 pr-3">
                                                        {k.email}
                                                    </td>
                                                    <td className="py-1 pr-3 font-mono">
                                                        {k.password}
                                                    </td>
                                                    <td className="py-1">
                                                        {k.role}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="nip">NIP</Label>
                                <Input
                                    id="nip"
                                    value={data.nip}
                                    onChange={(e) =>
                                        setData('nip', e.target.value)
                                    }
                                />
                                <InputError message={errors.nip} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="nama">Nama</Label>
                                <Input
                                    id="nama"
                                    value={data.nama}
                                    onChange={(e) =>
                                        setData('nama', e.target.value)
                                    }
                                />
                                <InputError message={errors.nama} />
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
                                <Label htmlFor="no_hp">No. HP</Label>
                                <Input
                                    id="no_hp"
                                    value={data.no_hp}
                                    onChange={(e) =>
                                        setData('no_hp', e.target.value)
                                    }
                                />
                                <InputError message={errors.no_hp} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="jenis_kelamin">
                                    Jenis Kelamin
                                </Label>
                                <select
                                    id="jenis_kelamin"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={data.jenis_kelamin}
                                    onChange={(e) =>
                                        setData('jenis_kelamin', e.target.value)
                                    }
                                >
                                    <option value="laki_laki">Laki-laki</option>
                                    <option value="perempuan">Perempuan</option>
                                </select>
                                <InputError message={errors.jenis_kelamin} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="departemen_id">
                                    Departemen
                                </Label>
                                <select
                                    id="departemen_id"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={data.departemen_id}
                                    onChange={(e) =>
                                        setData('departemen_id', e.target.value)
                                    }
                                >
                                    <option value="">Pilih departemen</option>
                                    {departemens.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.nama_departemen}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.departemen_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="jabatan_id">Jabatan</Label>
                                <select
                                    id="jabatan_id"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={data.jabatan_id}
                                    onChange={(e) =>
                                        setData('jabatan_id', e.target.value)
                                    }
                                >
                                    <option value="">Pilih jabatan</option>
                                    {jabatans.map((j) => (
                                        <option key={j.id} value={j.id}>
                                            {j.nama_jabatan}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.jabatan_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="tanggal_masuk">
                                    Tanggal Masuk
                                </Label>
                                <Input
                                    id="tanggal_masuk"
                                    type="date"
                                    value={data.tanggal_masuk}
                                    onChange={(e) =>
                                        setData('tanggal_masuk', e.target.value)
                                    }
                                />
                                <InputError message={errors.tanggal_masuk} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={data.status}
                                    onChange={(e) =>
                                        setData('status', e.target.value)
                                    }
                                >
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                                <InputError message={errors.status} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="tipe_karyawan">
                                    Tipe Karyawan
                                </Label>
                                <select
                                    id="tipe_karyawan"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={data.tipe_karyawan}
                                    onChange={(e) =>
                                        setData('tipe_karyawan', e.target.value)
                                    }
                                >
                                    <option value="tetap">
                                        Karyawan Tetap
                                    </option>
                                    <option value="kontrak">
                                        Karyawan Kontrak
                                    </option>
                                </select>
                                <InputError message={errors.tipe_karyawan} />
                            </div>
                            {data.tipe_karyawan === 'kontrak' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="tanggal_akhir_kontrak">
                                        Tanggal Akhir Kontrak
                                    </Label>
                                    <Input
                                        id="tanggal_akhir_kontrak"
                                        type="date"
                                        value={data.tanggal_akhir_kontrak}
                                        onChange={(e) =>
                                            setData(
                                                'tanggal_akhir_kontrak',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.tanggal_akhir_kontrak}
                                    />
                                </div>
                            )}
                            {!editing && (
                                <div className="col-span-2 grid gap-3 rounded-md border p-3 md:col-span-4">
                                    <label className="flex items-center gap-2 text-sm">
                                        <Checkbox
                                            checked={data.buat_akun}
                                            onCheckedChange={(checked) =>
                                                setData(
                                                    'buat_akun',
                                                    Boolean(checked),
                                                )
                                            }
                                        />
                                        Buat akun login sekaligus (pakai nama &
                                        email di atas)
                                    </label>
                                    {data.buat_akun && (
                                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="akun_password">
                                                    Password
                                                </Label>
                                                <Input
                                                    id="akun_password"
                                                    type="password"
                                                    value={data.akun_password}
                                                    onChange={(e) =>
                                                        setData(
                                                            'akun_password',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        errors.akun_password
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="akun_role">
                                                    Role
                                                </Label>
                                                <select
                                                    id="akun_role"
                                                    className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm"
                                                    value={data.akun_role}
                                                    onChange={(e) =>
                                                        setData(
                                                            'akun_role',
                                                            e.target.value,
                                                        )
                                                    }
                                                >
                                                    {OPSI_ROLE.map((role) => (
                                                        <option
                                                            key={role}
                                                            value={role}
                                                        >
                                                            {role}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={errors.akun_role}
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                            <div className="col-span-2 flex items-end gap-2 md:col-span-4">
                                <Button type="submit" disabled={processing}>
                                    {editing ? 'Simpan' : 'Tambah Karyawan'}
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

                <form onSubmit={runFilter} className="flex flex-wrap gap-3">
                    <Input
                        placeholder="Cari nama/NIP..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="max-w-sm"
                    />
                    <select
                        className="h-9 w-full rounded-md border border-input bg-transparent px-2 text-sm sm:w-auto"
                        value={departemenFilter}
                        onChange={(e) => setDepartemenFilter(e.target.value)}
                    >
                        <option value="">Semua Departemen</option>
                        {departemens.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.nama_departemen}
                            </option>
                        ))}
                    </select>
                    <Button type="submit" variant="outline">
                        Filter
                    </Button>
                </form>

                <Card>
                    <CardContent className="divide-y p-0">
                        {karyawans.data.map((karyawan) => (
                            <div
                                key={karyawan.id}
                                className="flex flex-col gap-3 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <div className="flex flex-wrap items-center gap-2 font-medium">
                                        {karyawan.nama}
                                        {karyawan.user?.roles.map((role) => (
                                            <Badge
                                                key={role.name}
                                                variant="outline"
                                            >
                                                {role.name}
                                            </Badge>
                                        ))}
                                        {karyawan.status === 'nonaktif' && (
                                            <Badge variant="destructive">
                                                nonaktif
                                            </Badge>
                                        )}
                                        {karyawan.tipe_karyawan ===
                                            'kontrak' && (
                                            <Badge variant="secondary">
                                                kontrak
                                            </Badge>
                                        )}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {karyawan.nip} &middot;{' '}
                                        {karyawan.departemen?.nama_departemen}{' '}
                                        &middot;{' '}
                                        {karyawan.jabatan?.nama_jabatan}
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => startEdit(karyawan)}
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => destroy(karyawan)}
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Pagination links={karyawans.links} />

                {auth.roles.includes('admin') && (
                    <Card className="border-red-200 dark:border-red-900">
                        <CardContent className="space-y-3">
                            <div className="flex items-start gap-3 text-red-700 dark:text-red-400">
                                <AlertTriangle className="mt-0.5 size-5 shrink-0" />
                                <div>
                                    <p className="font-medium">
                                        Zona Berbahaya
                                    </p>
                                    <p className="text-sm text-red-700/80 dark:text-red-400/80">
                                        Hapus SEMUA karyawan beserta akun login,
                                        riwayat cuti, saldo, dan jadwal shift
                                        mereka secara permanen — biasanya
                                        dipakai sebelum import data karyawan
                                        yang asli. Data Master (Departemen,
                                        Jabatan, Jenis Cuti, dll.) tidak ikut
                                        terhapus. Akun kamu sendiri tidak akan
                                        ikut terhapus.
                                    </p>
                                </div>
                            </div>

                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button variant="destructive">
                                        Reset Semua Data Karyawan
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogTitle>
                                        Reset semua data karyawan?
                                    </DialogTitle>
                                    <DialogDescription>
                                        Tindakan ini permanen dan tidak bisa
                                        dibatalkan. Untuk melanjutkan, ketik
                                        persis frasa berikut:{' '}
                                        <span className="font-mono font-semibold text-foreground">
                                            {FRASA_KONFIRMASI_RESET}
                                        </span>
                                    </DialogDescription>

                                    <form
                                        onSubmit={submitResetData}
                                        className="space-y-4"
                                    >
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="konfirmasi_reset"
                                                className="sr-only"
                                            >
                                                Frasa konfirmasi
                                            </Label>
                                            <Input
                                                id="konfirmasi_reset"
                                                value={konfirmasiReset}
                                                onChange={(e) =>
                                                    setKonfirmasiReset(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder={
                                                    FRASA_KONFIRMASI_RESET
                                                }
                                                autoComplete="off"
                                            />
                                        </div>

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button
                                                    type="button"
                                                    variant="secondary"
                                                    onClick={() =>
                                                        setKonfirmasiReset('')
                                                    }
                                                >
                                                    Batal
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                variant="destructive"
                                                disabled={
                                                    resetProcessing ||
                                                    konfirmasiReset !==
                                                        FRASA_KONFIRMASI_RESET
                                                }
                                            >
                                                Hapus Semua Karyawan
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

MasterKaryawan.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Master Karyawan', href: karyawanIndex() },
    ],
};
