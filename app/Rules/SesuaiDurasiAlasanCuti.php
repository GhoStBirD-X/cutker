<?php

namespace App\Rules;

use App\Models\AlasanCuti;
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

        $jumlahHari = (int) Carbon::parse($this->tanggalMulai)->diffInDays(Carbon::parse((string) $value)) + 1;

        if ($jumlahHari > $alasanCuti->jumlah_hari) {
            $fail("Jumlah hari melebihi ketentuan untuk alasan \"{$alasanCuti->nama_alasan}\" (maksimal {$alasanCuti->jumlah_hari} hari).");
        }
    }
}
