<?php

namespace App\Rules;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CutiBesarSetelahCutiTahunanHabis implements ValidationRule
{
    public function __construct(
        protected Karyawan $karyawan,
    ) {}

    /**
     * Menolak Cuti Besar selama karyawan masih punya saldo Cuti Tahunan
     * aktif — Cuti Besar baru boleh dipakai setelah Cuti Tahunan habis.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $jenisCuti = JenisCuti::query()->find((int) $value);

        if ($jenisCuti?->nama_jenis !== JenisCuti::NAMA_CUTI_BESAR) {
            return;
        }

        if ($this->karyawan->masihPunyaSaldoCutiTahunan()) {
            $fail('Cuti Besar baru bisa diajukan setelah saldo Cuti Tahunan Anda habis.');
        }
    }
}
