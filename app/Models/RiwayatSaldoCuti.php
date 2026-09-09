<?php

namespace App\Models;

use Database\Factories\RiwayatSaldoCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'karyawan_id',
    'jenis_cuti_id',
    'periode_ke',
    'periode_mulai',
    'periode_selesai',
    'kuota',
    'terpakai',
    'sisa',
])]
class RiwayatSaldoCuti extends Model
{
    /** @use HasFactory<RiwayatSaldoCutiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
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
     * @return HasMany<KompensasiCuti, $this>
     */
    public function kompensasiCutis(): HasMany
    {
        return $this->hasMany(KompensasiCuti::class);
    }
}
