<?php

namespace App\Models;

use Database\Factories\SaldoCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
