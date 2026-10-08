import { Form, Head, router, usePage } from '@inertiajs/react';
import { UserPen } from 'lucide-react';
import { useMemo, useState } from 'react';
import InputCutiKaryawanController from '@/actions/App/Http/Controllers/Cuti/InputCutiKaryawanController';
import InputError from '@/components/input-error';
import { PratinjauHariCuti } from '@/components/pratinjau-hari-cuti';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { saldoSeverity } from '@/lib/saldo-severity';
import { dashboard } from '@/routes';
import { create as inputKaryawanCreate } from '@/routes/cuti/input-karyawan';
import type { AlasanCuti, JenisCuti, Karyawan, SaldoCuti } from '@/types';

type PageProps = {
    karyawans: Pick<Karyawan, 'id' | 'nama' | 'nip'>[];
    karyawanId: number | null;
    jenisCutis?: JenisCuti[];
    alasanCutis?: AlasanCuti[];
    saldoCuti?: SaldoCuti[];
    cutiBesarTerkunci?: boolean;
    hariLibur?: string[];
};

/**
 * HRD/Admin mencatat cuti atas nama karyawan, termasuk cuti yang sudah
 * lewat (backdate tanpa batas). Langsung disetujui & memotong saldo aktif.
 * Data form (jenis cuti, saldo) dimuat ulang dari server setiap kali
 * karyawan dipilih.
 */
export default function InputCutiKaryawan() {
    const {
        karyawans,
        karyawanId,
        jenisCutis = [],
        alasanCutis = [],
        saldoCuti = [],
        cutiBesarTerkunci = false,
        hariLibur = [],
    } = usePage<PageProps>().props;

    const [jenisCutiId, setJenisCutiId] = useState('');
    const [alasanCutiId, setAlasanCutiId] = useState('');
    const [tanggalMulai, setTanggalMulai] = useState('');
    const [tanggalSelesai, setTanggalSelesai] = useState('');

    const saldoTerpilih = saldoCuti.find(
        (saldo) => String(saldo.jenis_cuti_id) === jenisCutiId,
    );

    const alasanUntukJenisTerpilih = useMemo(
        () =>
            alasanCutis.filter(
                (alasan) => String(alasan.jenis_cuti_id) === jenisCutiId,
            ),
        [alasanCutis, jenisCutiId],
    );

    function pilihKaryawan(value: string) {
        setJenisCutiId('');
        setAlasanCutiId('');
        router.get(
            inputKaryawanCreate({ query: { karyawan_id: value } }),
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Input Cuti Karyawan" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <UserPen className="size-5 text-primary" />
                    Input Cuti Karyawan
                </h1>
                <p className="max-w-xl text-sm text-muted-foreground">
                    Catat cuti atas nama karyawan, termasuk cuti yang sudah
                    lewat. Cuti yang dicatat di sini langsung disetujui tanpa
                    alur approval dan memotong saldo periode yang sedang
                    berjalan.
                </p>

                <Card className="max-w-xl">
                    <CardContent>
                        <Form
                            {...InputCutiKaryawanController.store.form()}
                            encType="multipart/form-data"
                            className="space-y-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="karyawan_id">
                                            Karyawan
                                        </Label>
                                        <Select
                                            name="karyawan_id"
                                            required
                                            value={
                                                karyawanId
                                                    ? String(karyawanId)
                                                    : ''
                                            }
                                            onValueChange={pilihKaryawan}
                                        >
                                            <SelectTrigger
                                                id="karyawan_id"
                                                className="w-full"
                                            >
                                                <SelectValue placeholder="Pilih karyawan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {karyawans.map((karyawan) => (
                                                    <SelectItem
                                                        key={karyawan.id}
                                                        value={String(
                                                            karyawan.id,
                                                        )}
                                                    >
                                                        {karyawan.nama}{' '}
                                                        <span className="text-muted-foreground">
                                                            ({karyawan.nip})
                                                        </span>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.karyawan_id}
                                        />
                                    </div>

                                    {karyawanId && (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="jenis_cuti_id">
                                                    Jenis Cuti
                                                </Label>
                                                <Select
                                                    name="jenis_cuti_id"
                                                    required
                                                    value={jenisCutiId}
                                                    onValueChange={(value) => {
                                                        setJenisCutiId(value);
                                                        setAlasanCutiId('');
                                                    }}
                                                >
                                                    <SelectTrigger
                                                        id="jenis_cuti_id"
                                                        className="w-full"
                                                    >
                                                        <SelectValue placeholder="Pilih jenis cuti" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {jenisCutis.map(
                                                            (jenis) => {
                                                                const saldo =
                                                                    saldoCuti.find(
                                                                        (s) =>
                                                                            s.jenis_cuti_id ===
                                                                            jenis.id,
                                                                    );
                                                                const style =
                                                                    saldo
                                                                        ? saldoSeverity(
                                                                              saldo.sisa,
                                                                              saldo.kuota,
                                                                          )
                                                                        : null;

                                                                return (
                                                                    <SelectItem
                                                                        key={
                                                                            jenis.id
                                                                        }
                                                                        value={String(
                                                                            jenis.id,
                                                                        )}
                                                                    >
                                                                        {
                                                                            jenis.nama_jenis
                                                                        }{' '}
                                                                        {saldo &&
                                                                            style && (
                                                                                <span
                                                                                    className={
                                                                                        style.text
                                                                                    }
                                                                                >
                                                                                    {saldo.kuota ===
                                                                                    null
                                                                                        ? '(tanpa batas)'
                                                                                        : `(sisa ${saldo.sisa} hari)`}
                                                                                </span>
                                                                            )}
                                                                    </SelectItem>
                                                                );
                                                            },
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={
                                                        errors.jenis_cuti_id
                                                    }
                                                />
                                                {cutiBesarTerkunci && (
                                                    <p className="text-xs text-muted-foreground">
                                                        Cuti Besar baru bisa
                                                        dipilih setelah saldo
                                                        Cuti Tahunan karyawan
                                                        ini habis.
                                                    </p>
                                                )}
                                            </div>

                                            {alasanUntukJenisTerpilih.length >
                                                0 && (
                                                <div className="grid gap-2">
                                                    <Label htmlFor="alasan_cuti_id">
                                                        Alasan Cuti
                                                    </Label>
                                                    <Select
                                                        name="alasan_cuti_id"
                                                        required
                                                        value={alasanCutiId}
                                                        onValueChange={
                                                            setAlasanCutiId
                                                        }
                                                    >
                                                        <SelectTrigger
                                                            id="alasan_cuti_id"
                                                            className="w-full"
                                                        >
                                                            <SelectValue placeholder="Pilih alasan" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {alasanUntukJenisTerpilih.map(
                                                                (alasan) => (
                                                                    <SelectItem
                                                                        key={
                                                                            alasan.id
                                                                        }
                                                                        value={String(
                                                                            alasan.id,
                                                                        )}
                                                                    >
                                                                        {
                                                                            alasan.nama_alasan
                                                                        }{' '}
                                                                        {alasan.jumlah_hari ===
                                                                        null
                                                                            ? '(tanpa batas)'
                                                                            : `(maks ${alasan.jumlah_hari} hari)`}
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError
                                                        message={
                                                            errors.alasan_cuti_id
                                                        }
                                                    />
                                                </div>
                                            )}

                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="tanggal_mulai">
                                                        Tanggal Mulai
                                                    </Label>
                                                    <Input
                                                        id="tanggal_mulai"
                                                        type="date"
                                                        name="tanggal_mulai"
                                                        value={tanggalMulai}
                                                        onChange={(e) =>
                                                            setTanggalMulai(
                                                                e.target.value,
                                                            )
                                                        }
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.tanggal_mulai
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="tanggal_selesai">
                                                        Tanggal Selesai
                                                    </Label>
                                                    <Input
                                                        id="tanggal_selesai"
                                                        type="date"
                                                        name="tanggal_selesai"
                                                        min={
                                                            tanggalMulai ||
                                                            undefined
                                                        }
                                                        value={tanggalSelesai}
                                                        onChange={(e) =>
                                                            setTanggalSelesai(
                                                                e.target.value,
                                                            )
                                                        }
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.tanggal_selesai
                                                        }
                                                    />
                                                </div>
                                            </div>

                                            <PratinjauHariCuti
                                                tanggalMulai={tanggalMulai}
                                                tanggalSelesai={tanggalSelesai}
                                                hariLibur={hariLibur}
                                                saldo={saldoTerpilih}
                                            />

                                            <div className="grid gap-2">
                                                <Label htmlFor="alasan">
                                                    Keterangan
                                                </Label>
                                                <Textarea
                                                    id="alasan"
                                                    name="alasan"
                                                    placeholder="Keperluan cuti karyawan."
                                                    maxLength={255}
                                                    required
                                                />
                                                <InputError
                                                    message={errors.alasan}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="lampiran">
                                                    Lampiran (opsional: surat
                                                    dokter dll. — PDF/JPG/PNG,
                                                    maks. 2 MB)
                                                </Label>
                                                <Input
                                                    id="lampiran"
                                                    type="file"
                                                    name="lampiran"
                                                    accept=".pdf,.jpg,.jpeg,.png"
                                                />
                                                <InputError
                                                    message={errors.lampiran}
                                                />
                                            </div>

                                            <Button disabled={processing}>
                                                {processing && <Spinner />}
                                                Catat Cuti
                                            </Button>
                                        </>
                                    )}
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

InputCutiKaryawan.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Input Cuti Karyawan', href: inputKaryawanCreate() },
    ],
};
