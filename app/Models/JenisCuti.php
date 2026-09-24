<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use Database\Factories\JenisCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nama_jenis
 * @property int|null $kuota_default
 * @property int|null $masa_kerja_minimal_bulan
 * @property int|null $minimal_hari_pengajuan
 * @property JenisKelamin|null $khusus_gender
 * @property string|null $keterangan
 */
#[Fillable(['nama_jenis', 'kuota_default', 'masa_kerja_minimal_bulan', 'minimal_hari_pengajuan', 'khusus_gender', 'keterangan'])]
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

    /**
     * Jenis cuti yang tidak dikhususkan untuk gender tertentu, atau yang
     * khusus_gender-nya cocok dengan $jenisKelamin (mis. Cuti Hamil/Haid
     * disembunyikan dari karyawan laki-laki).
     *
     * @param  Builder<JenisCuti>  $query
     * @return Builder<JenisCuti>
     */
    public function scopeSesuaiGender(Builder $query, JenisKelamin $jenisKelamin): Builder
    {
        return $query->where(function (Builder $query) use ($jenisKelamin) {
            $query->whereNull('khusus_gender')->orWhere('khusus_gender', $jenisKelamin);
        });
    }
}
