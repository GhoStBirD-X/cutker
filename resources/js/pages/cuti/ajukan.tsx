import { Form, Head, Link, usePage } from '@inertiajs/react';
import { CalendarPlus, Zap } from 'lucide-react';
import { useMemo, useState } from 'react';
import PengajuanCutiController from '@/actions/App/Http/Controllers/Cuti/PengajuanCutiController';
import InputError from '@/components/input-error';
import { PratinjauHariCuti } from '@/components/pratinjau-hari-cuti';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { tanggalHariIni } from '@/lib/hari-kerja';
import { saldoSeverity } from '@/lib/saldo-severity';
import { dashboard } from '@/routes';
import {
    create as cutiCreate,
    createMendadak as cutiCreateMendadak,
    index as cutiIndex,
} from '@/routes/cuti';
import type { AlasanCuti, JenisCuti, SaldoCuti } from '@/types';

type PageProps = {
    jenisCutis: JenisCuti[];
    alasanCutis: AlasanCuti[];
    saldoCuti: SaldoCuti[];
    cutiBesarTerkunci: boolean;
    hariLibur: string[];
};

export default function CutiAjukan() {
    const { jenisCutis, alasanCutis, saldoCuti, cutiBesarTerkunci, hariLibur } =
        usePage<PageProps>().props;
    // Pengajuan biasa paling cepat dimulai besok (H+1); tanggal mulai
    // kemarin/hari ini diarahkan ke form cuti mendadak lewat dialog.
    const besok = tanggalHariIni(1);
    const kemarin = tanggalHariIni(-1);
    const [arahkanKeMendadak, setArahkanKeMendadak] = useState(false);

    const [jenisCutiId, setJenisCutiId] = useState('');
    const [alasanCutiId, setAlasanCutiId] = useState('');
    const [tanggalMulai, setTanggalMulai] = useState('');
    const [tanggalSelesai, setTanggalSelesai] = useState('');

    const saldoTerpilih = saldoCuti.find(
        (saldo) => String(saldo.jenis_cuti_id) === jenisCutiId,
    );

    const cekPerluCutiMendadak = () => {
        if (tanggalMulai >= kemarin && tanggalMulai < besok) {
            setArahkanKeMendadak(true);
        }
    };

    const alasanUntukJenisTerpilih = useMemo(
        () =>
            alasanCutis.filter(
                (alasan) => String(alasan.jenis_cuti_id) === jenisCutiId,
            ),
        [alasanCutis, jenisCutiId],
    );

    return (
        <>
            <Head title="Ajukan Cuti" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="flex items-center gap-2 text-xl font-semibold">
                    <CalendarPlus className="size-5 text-primary" />
                    Ajukan Cuti
                </h1>

                <Card className="max-w-xl">
                    <CardContent>
                        <Form
                            {...PengajuanCutiController.store.form()}
                            encType="multipart/form-data"
                            className="space-y-5"
                            onError={(errors) => {
                                if (errors.tanggal_mulai) {
                                    cekPerluCutiMendadak();
                                }
                            }}
                        >
                            {({ processing, errors }) => (
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
                                                {jenisCutis.map((jenis) => {
                                                    const saldo =
                                                        saldoCuti.find(
                                                            (s) =>
                                                                s.jenis_cuti_id ===
                                                                jenis.id,
                                                        );

                                                    const style = saldo
                                                        ? saldoSeverity(
                                                              saldo.sisa,
                                                              saldo.kuota,
                                                          )
                                                        : null;

                                                    return (
                                                        <SelectItem
                                                            key={jenis.id}
                                                            value={String(
                                                                jenis.id,
                                                            )}
                                                        >
                                                            {jenis.nama_jenis}{' '}
                                                            {saldo && style && (
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
                                                })}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.jenis_cuti_id}
                                        />
                                        {cutiBesarTerkunci && (
                                            <p className="text-xs text-muted-foreground">
                                                Cuti Besar baru bisa dipilih
                                                setelah saldo Cuti Tahunan
                                                habis.
                                            </p>
                                        )}
                                    </div>

                                    {alasanUntukJenisTerpilih.length > 0 && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="alasan_cuti_id">
                                                Alasan Cuti
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
                                                                    ? '(tanpa batas)'
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
                                                min={besok}
                                                value={tanggalMulai}
                                                onChange={(e) =>
                                                    setTanggalMulai(
                                                        e.target.value,
                                                    )
                                                }
                                                onBlur={cekPerluCutiMendadak}
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
                                                min={tanggalMulai || besok}
                                                value={tanggalSelesai}
                                                onChange={(e) =>
                                                    setTanggalSelesai(
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <InputError
                                                message={errors.tanggal_selesai}
                                            />
                                        </div>
                                    </div>

                                    <p className="-mt-3 text-xs text-muted-foreground">
                                        Butuh cuti mulai hari ini atau kemarin?{' '}
                                        <Link
                                            href={cutiCreateMendadak()}
                                            className="text-primary underline"
                                        >
                                            Ajukan cuti mendadak
                                        </Link>
                                        .
                                    </p>

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
                                            placeholder="Ceritakan singkat keperluan cuti Anda."
                                            maxLength={255}
                                            required
                                        />
                                        <InputError message={errors.alasan} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="lampiran">
                                            Lampiran (opsional: surat dokter
                                            dll. — PDF/JPG/PNG, maks. 2 MB)
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
                                        {processing && <Spinner />}
                                        Ajukan Cuti
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={arahkanKeMendadak}
                onOpenChange={setArahkanKeMendadak}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Zap className="size-5 text-amber-600 dark:text-amber-400" />
                            Gunakan Pengajuan Cuti Mendadak
                        </DialogTitle>
                        <DialogDescription>
                            Cuti biasa paling cepat dimulai besok (H+1). Untuk
                            cuti yang dimulai hari ini atau kemarin, ajukan
                            lewat form Cuti Mendadak.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="secondary"
                            onClick={() => setArahkanKeMendadak(false)}
                        >
                            Ganti Tanggal
                        </Button>
                        <Button asChild>
                            <Link href={cutiCreateMendadak()}>
                                Ke Ajukan Cuti Mendadak
                            </Link>
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

CutiAjukan.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Riwayat Cuti', href: cutiIndex() },
        { title: 'Ajukan Cuti', href: cutiCreate() },
    ],
};
