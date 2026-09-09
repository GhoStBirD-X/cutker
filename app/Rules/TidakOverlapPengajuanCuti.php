<?php

namespace App\Rules;

use App\Enums\StatusPengajuan;
use App\Models\PengajuanCuti;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class TidakOverlapPengajuanCuti implements ValidationRule
{
    public function __construct(
        protected int $karyawanId,
        protected string $tanggalMulai,
    ) {}

    /**
     * Menolak jika rentang tanggal bertabrakan dengan pengajuan cuti aktif
     * (pending/disetujui) lain milik karyawan yang sama.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tanggalSelesai = (string) $value;

        // whereDate (bukan where biasa) supaya perbandingan tanggal tidak
        // terganggu oleh format waktu tersimpan (kolom date-cast disimpan
        // sebagai "Y-m-d H:i:s" oleh grammar koneksi, bukan "Y-m-d" polos).
        $bentrok = PengajuanCuti::query()
            ->where('karyawan_id', $this->karyawanId)
            ->whereIn('status', [StatusPengajuan::Pending, StatusPengajuan::Disetujui])
            ->whereDate('tanggal_mulai', '<=', $tanggalSelesai)
            ->whereDate('tanggal_selesai', '>=', $this->tanggalMulai)
            ->exists();

        if ($bentrok) {
            $fail('Rentang tanggal bertabrakan dengan pengajuan cuti aktif lain milik Anda.');
        }
    }
}
