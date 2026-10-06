<?php

namespace App\Rules;

use App\Enums\TipeKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CutiBesarKhususKaryawanTetap implements ValidationRule
{
    public function __construct(
        protected Karyawan $karyawan,
    ) {}

    /**
     * Menolak jenis cuti khusus karyawan tetap (Cuti Besar) untuk karyawan
     * kontrak — kontrak berjalan dalam siklus K1–K5 dan tidak pernah
     * mendapat Cuti Besar.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $jenisCuti = JenisCuti::query()->find((int) $value);

        if ($jenisCuti?->khususKaryawanTetap() && $this->karyawan->tipe_karyawan === TipeKaryawan::Kontrak) {
            $fail("{$jenisCuti->nama_jenis} hanya berlaku untuk karyawan tetap.");
        }
    }
}
