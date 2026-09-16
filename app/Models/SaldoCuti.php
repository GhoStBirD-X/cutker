<?php

namespace App\Models;

use Database\Factories\SaldoCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $karyawan_id
 * @property int $jenis_cuti_id
 * @property int $tahun
 * @property int|null $periode_ke
 * @property Carbon|null $periode_mulai
 * @property Carbon|null $periode_selesai
 * @property int|null $kuota
 * @property int $terpakai
 * @property int|null $sisa
 * @property Carbon|null $ditutup_pada
 * @property string|null $catatan
 * @property int|null $diubah_oleh_id
 * @property Carbon|null $diubah_pada
 */
#[Fillable([
    'karyawan_id',
    'jenis_cuti_id',
    'tahun',
    'periode_ke',
    'periode_mulai',
    'periode_selesai',
    'kuota',
    'terpakai',
    'sisa',
    'ditutup_pada',
    'catatan',
    'diubah_oleh_id',
    'diubah_pada',
])]
class SaldoCuti extends Model
{
    /** @use HasFactory<SaldoCutiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
            'ditutup_pada' => 'datetime',
            'diubah_pada' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Karyawan, $this>
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    /**
     * @return BelongsTo<JenisCuti, $this>
     */
    public function jenisCuti(): BelongsTo
    {
        return $this->belongsTo(JenisCuti::class);
    }

    /**
     * @return BelongsTo<Karyawan, $this>
     */
    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'diubah_oleh_id');
    }

    /**
     * Baris saldo yang masih berjalan (belum ditutup oleh reset kalender
     * maupun siklus periode tahunan).
     *
     * @param  Builder<SaldoCuti>  $query
     * @return Builder<SaldoCuti>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereNull('ditutup_pada');
    }
}
