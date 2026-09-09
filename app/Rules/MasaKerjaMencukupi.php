<?php

namespace App\Rules;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MasaKerjaMencukupi implements ValidationRule
{
    public function __construct(
        protected Karyawan $karyawan,
    ) {}

    /**
     * Menolak jika jenis cuti yang dipilih mensyaratkan masa kerja minimal
     * (mis. Cuti Tahunan 12 bulan, Cuti Besar 60 bulan) dan karyawan belum
     * memenuhinya berdasarkan tanggal_masuk.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $jenisCuti = JenisCuti::query()->find((int) $value);

        if (! $jenisCuti || $jenisCuti->masa_kerja_minimal_bulan === null) {
            return;
        }

        $masaKerjaBulan = (int) $this->karyawan->tanggal_masuk->diffInMonths(now());

        if ($masaKerjaBulan < $jenisCuti->masa_kerja_minimal_bulan) {
            $fail("Anda belum memenuhi syarat masa kerja minimal {$jenisCuti->masa_kerja_minimal_bulan} bulan untuk mengajukan {$jenisCuti->nama_jenis}.");
        }
    }
}
