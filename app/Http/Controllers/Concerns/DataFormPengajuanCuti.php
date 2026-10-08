<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\TipeKaryawan;
use App\Models\AlasanCuti;
use App\Models\HariLibur;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Carbon\CarbonInterface;

trait DataFormPengajuanCuti
{
    /**
     * Data form pengajuan cuti untuk satu karyawan: jenis cuti yang berlaku
     * baginya, alasan cuti, dan saldo aktifnya. `hariLibur` (tanggal libur
     * terdaftar dari $liburMulai s/d setahun ke depan) dipakai form untuk
     * menampilkan pratinjau jumlah hari kerja sebelum dikirim, dengan aturan
     * yang sama seperti HariLiburService::hitungHariLibur().
     *
     * @return array<string, mixed>
     */
    protected function dataFormCuti(Karyawan $karyawan, CarbonInterface $liburMulai): array
    {
        // Kontrak tidak pernah mendapat Cuti Besar, jadi petunjuk "terkunci"
        // hanya relevan untuk karyawan tetap.
        $cutiBesarTerkunci = $karyawan->tipe_karyawan === TipeKaryawan::Tetap
            && $karyawan->masihPunyaSaldoCutiTahunan();

        return [
            'jenisCutis' => JenisCuti::query()
                ->sesuaiGender($karyawan->jenis_kelamin)
                ->berlakuUntukTipe($karyawan->tipe_karyawan)
                ->when($cutiBesarTerkunci, fn ($query) => $query->where('nama_jenis', '!=', JenisCuti::NAMA_CUTI_BESAR))
                ->get(),
            'cutiBesarTerkunci' => $cutiBesarTerkunci,
            'alasanCutis' => AlasanCuti::all(),
            'saldoCuti' => SaldoCuti::query()
                ->with('jenisCuti')
                ->where('karyawan_id', $karyawan->id)
                ->aktif()
                ->whereHas('jenisCuti', fn ($query) => $query->sesuaiGender($karyawan->jenis_kelamin)->berlakuUntukTipe($karyawan->tipe_karyawan))
                ->get(),
            'hariLibur' => HariLibur::query()
                ->whereBetween('tanggal', [$liburMulai->toDateString(), today()->addYear()->toDateString()])
                ->orderBy('tanggal')
                ->pluck('tanggal')
                ->map(fn ($tanggal) => $tanggal->toDateString()),
        ];
    }
}
