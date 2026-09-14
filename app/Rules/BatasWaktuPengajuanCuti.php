<?php

namespace App\Rules;

use App\Models\JenisCuti;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Translation\PotentiallyTranslatedString;

class BatasWaktuPengajuanCuti implements ValidationRule
{
    public function __construct(
        protected ?int $jenisCutiId,
        protected bool $mendadak,
    ) {}

    /**
     * Menolak jika jenis cuti yang dipilih mensyaratkan jarak minimal antara
     * tanggal pengajuan dan tanggal mulai (mis. Cuti Tahunan H-7), kecuali
     * pengajuan ditandai mendadak (lihat App\Http\Requests\Cuti\StorePengajuanCutiRequest).
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->mendadak) {
            return;
        }

        $jenisCuti = JenisCuti::query()->find($this->jenisCutiId);

        if (! $jenisCuti || $jenisCuti->minimal_hari_pengajuan === null) {
            return;
        }

        $hariMenjelang = (int) now()->startOfDay()->diffInDays(Carbon::parse($value)->startOfDay(), false);

        if ($hariMenjelang < $jenisCuti->minimal_hari_pengajuan) {
            $fail("Pengajuan {$jenisCuti->nama_jenis} harus diajukan minimal {$jenisCuti->minimal_hari_pengajuan} hari sebelum tanggal mulai. Jika ini kondisi mendadak, gunakan form Pengajuan Cuti Mendadak.");
        }
    }
}
