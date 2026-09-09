<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use Database\Factories\JenisCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_jenis', 'kuota_default', 'masa_kerja_minimal_bulan', 'khusus_gender', 'keterangan'])]
class JenisCuti extends Model
{
    /** @use HasFactory<JenisCutiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'khusus_gender' => JenisKelamin::class,
        ];
    }

    /**
     * @return HasMany<PengajuanCuti, $this>
     */
    public function pengajuanCutis(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class);
    }

    /**
     * @return HasMany<SaldoCuti, $this>
     */
    public function saldoCutis(): HasMany
    {
        return $this->hasMany(SaldoCuti::class);
    }

    /**
     * @return HasMany<AlasanCuti, $this>
     */
    public function alasanCutis(): HasMany
    {
        return $this->hasMany(AlasanCuti::class);
    }
}
