<?php

namespace App\Rules;

use App\Models\AlasanCuti;
use App\Services\HariLiburService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;

class SesuaiDurasiAlasanCuti implements ValidationRule
{
    public function __construct(
        protected string $tanggalMulai,
        protected ?int $alasanCutiId,
    ) {}

    /**
     * Menolak jika jumlah hari pengajuan melebihi jumlah_hari yang ditetapkan
     * untuk alasan cuti lain-lain yang dipilih (mis. menikah maksimal 3 hari).
     * Dihitung dalam hari kerja — dikurangi Sabtu/Minggu & hari libur
     * terdaftar — sama seperti jumlah_hari yang dipotong dari saldo, supaya
     * mis. Jumat–Senin (2 hari kerja) lolos untuk alasan maksimal 2 hari.
     * Alasan dengan jumlah_hari null (hingga masalah selesai) tidak dibatasi.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->alasanCutiId) {
            return;
        }

        $alasanCuti = AlasanCuti::query()->find($this->alasanCutiId);

        if (! $alasanCuti || $alasanCuti->jumlah_hari === null) {
            return;
        }

        $mulai = Carbon::parse($this->tanggalMulai);
        $selesai = Carbon::parse((string) $value);
        $jumlahHariKalender = (int) $mulai->diffInDays($selesai) + 1;
        $jumlahHari = $jumlahHariKalender - app(HariLiburService::class)->hitungHariLibur($mulai, $selesai);

        if ($jumlahHari > $alasanCuti->jumlah_hari) {
            $fail("Jumlah hari melebihi ketentuan untuk alasan \"{$alasanCuti->nama_alasan}\" (maksimal {$alasanCuti->jumlah_hari} hari).");
        }
    }
}
