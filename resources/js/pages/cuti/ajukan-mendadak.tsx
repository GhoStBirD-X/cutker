import { Form, Head, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PengajuanCutiController from '@/actions/App/Http/Controllers/Cuti/PengajuanCutiController';
import InputError from '@/components/input-error';
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
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import { createMendadak as cutiCreateMendadak, index as cutiIndex } from '@/routes/cuti';
import type { AlasanCuti, JenisCuti, SaldoCuti } from '@/types';

type PageProps = {
    jenisCutis: JenisCuti[];
    alasanCutis: AlasanCuti[];
    saldoCuti: SaldoCuti[];
};

export default function CutiAjukanMendadak() {
    const { jenisCutis, alasanCutis, saldoCuti } = usePage<PageProps>().props;
    const today = new Date().toISOString().slice(0, 10);

    const [jenisCutiId, setJenisCutiId] = useState('');
    const [alasanCutiId, setAlasanCutiId] = useState('');

    const alasanUntukJenisTerpilih = useMemo(
        () =>
            alasanCutis.filter(
                (alasan) => String(alasan.jenis_cuti_id) === jenisCutiId,
            ),
        [alasanCutis, jenisCutiId],
    );

    return (
        <>
            <Head title="Ajukan Cuti Mendadak" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        Ajukan Cuti Mendadak
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Gunakan form ini hanya untuk kondisi mendesak yang
                        tidak memenuhi batas waktu pengajuan normal. Pengajuan
                        tetap harus disetujui atasan, dan alasan mendadak yang
                        Anda isi akan ditampilkan ke approver.
                    </p>
                </div>

                <Card className="max-w-xl">
                    <CardContent>
                        <Form
                            {...PengajuanCutiController.store.form()}
                            encType="multipart/form-data"
                            className="space-y-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="mendadak"
                                        value="1"
                                    />

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
                                                {jenisCutis.map((jenis) => {
                                                    const saldo =
                                                        saldoCuti.find(
                                                            (s) =>
                                                                s.jenis_cuti_id ===
                                                                jenis.id,
                                                        );

                                                    return (
                                                        <SelectItem
                                                            key={jenis.id}
                                                            value={String(
                                                                jenis.id,
                                                            )}
                                                        >
                                                            {jenis.nama_jenis}{' '}
                                                            {saldo
                                                                ? saldo.kuota ===
                                                                  null
                                                                    ? '(hari ∞)'
                                                                    : `(sisa ${saldo.sisa} hari)`
                                                                : ''}
                                                        </SelectItem>
                                                    );
                                                })}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.jenis_cuti_id}
                                        />
                                    </div>

                                    {alasanUntukJenisTerpilih.length > 0 && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="alasan_cuti_id">
                                                Alasan
                                            </Label>
                                            <Select
                                                name="alasan_cuti_id"
                                                required
                                                value={alasanCutiId}
                                                onValueChange={setAlasanCutiId}
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
                                                                key={alasan.id}
                                                                value={String(
                                                                    alasan.id,
                                                                )}
                                                            >
                                                                {
                                                                    alasan.nama_alasan
                                                                }{' '}
                                                                {alasan.jumlah_hari ===
                                                                null
                                                                    ? '(hari ∞)'
                                                                    : `(maks ${alasan.jumlah_hari} hari)`}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.alasan_cuti_id}
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
                                                min={today}
                                                required
                                            />
                                            <InputError
                                                message={errors.tanggal_mulai}
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
                                                min={today}
                                                required
                                            />
                                            <InputError
                                                message={errors.tanggal_selesai}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="alasan">Alasan</Label>
                                        <Textarea
                                            id="alasan"
                                            name="alasan"
                                            maxLength={255}
                                            required
                                        />
                                        <InputError message={errors.alasan} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="alasan_mendadak">
                                            Alasan Mendadak
                                        </Label>
                                        <Textarea
                                            id="alasan_mendadak"
                                            name="alasan_mendadak"
                                            maxLength={500}
                                            placeholder="Jelaskan kondisi mendesak yang membuat cuti ini tidak bisa diajukan sesuai batas waktu normal."
                                            required
                                        />
                                        <InputError
                                            message={errors.alasan_mendadak}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="lampiran">
                                            Lampiran (opsional, surat dokter
                                            dll.)
                                        </Label>
                                        <Input
                                            id="lampiran"
                                            type="file"
                                            name="lampiran"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                        />
                                        <InputError message={errors.lampiran} />
                                    </div>

                                    <Button disabled={processing}>
                                        Ajukan Cuti Mendadak
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CutiAjukanMendadak.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Riwayat Cuti', href: cutiIndex() },
        { title: 'Ajukan Cuti Mendadak', href: cutiCreateMendadak() },
    ],
};
