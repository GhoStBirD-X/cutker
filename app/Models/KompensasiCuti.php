<?php

namespace App\Models;

use App\Enums\StatusKompensasiCuti;
use Database\Factories\KompensasiCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'karyawan_id',
    'jenis_cuti_id',
    'riwayat_saldo_cuti_id',
    'jumlah_hari',
    'rate_per_hari',
    'total_rupiah',
    'status',
    'diproses_oleh_id',
    'diproses_pada',
    'catatan',
])]
class KompensasiCuti extends Model
{
    /** @use HasFactory<KompensasiCutiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusKompensasiCuti::class,
            'rate_per_hari' => 'decimal:2',
            'total_rupiah' => 'decimal:2',
            'diproses_pada' => 'datetime',
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
     * @return BelongsTo<RiwayatSaldoCuti, $this>
     */
    public function riwayatSaldoCuti(): BelongsTo
    {
        return $this->belongsTo(RiwayatSaldoCuti::class);
    }

    /**
     * @return BelongsTo<Karyawan, $this>
     */
    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'diproses_oleh_id');
    }
}
