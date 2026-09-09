<?php

namespace App\Rules;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SesuaiGenderJenisCuti implements ValidationRule
{
    public function __construct(
        protected Karyawan $karyawan,
    ) {}

    /**
     * Menolak jika jenis cuti yang dipilih khusus untuk gender tertentu
     * (mis. Cuti Hamil/Cuti Haid untuk perempuan) dan tidak sesuai dengan
     * jenis_kelamin karyawan.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $jenisCuti = JenisCuti::query()->find((int) $value);

        if (! $jenisCuti || $jenisCuti->khusus_gender === null) {
            return;
        }

        if ($jenisCuti->khusus_gender !== $this->karyawan->jenis_kelamin) {
            $fail("{$jenisCuti->nama_jenis} hanya berlaku untuk karyawan {$jenisCuti->khusus_gender->label()}.");
        }
    }
}
