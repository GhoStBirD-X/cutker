<?php

namespace App\Rules;

use App\Models\JadwalShift;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class TidakBentrokJadwalShift implements ValidationRule
{
    public function __construct(
        protected int $karyawanId,
        protected string $tanggalMulai,
    ) {}

    /**
     * Menolak jika karyawan sudah punya jadwal shift terjadwal di rentang
     * tanggal yang diajukan, karena jadwal tersebut perlu ditukar dulu
     * sebelum cuti bisa diajukan untuk tanggal tersebut.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $bentrok = JadwalShift::query()
            ->where('karyawan_id', $this->karyawanId)
            ->whereBetween('tanggal', [$this->tanggalMulai, (string) $value])
            ->exists();

        if ($bentrok) {
            $fail('Anda sudah memiliki jadwal shift pada rentang tanggal ini. Tukar jadwal shift terlebih dahulu sebelum mengajukan cuti.');
        }
    }
}
