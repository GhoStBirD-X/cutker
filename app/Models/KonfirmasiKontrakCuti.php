<?php

namespace App\Models;

use App\Enums\StatusKonfirmasiKontrak;
use Database\Factories\KonfirmasiKontrakCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'karyawan_id',
    'saldo_cuti_id',
    'periode_ke',
    'tanggal_batas',
    'status',
    'dikonfirmasi_oleh_id',
    'dikonfirmasi_pada',
    'catatan',
])]
class KonfirmasiKontrakCuti extends Model
{
    /** @use HasFactory<KonfirmasiKontrakCutiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusKonfirmasiKontrak::class,
            // Format eksplisit "Y-m-d" (bukan default ISO datetime) supaya
            // nilai yang dikirim ke frontend bisa langsung dipakai sebagai
            // atribut "min" pada input tanggal HTML.
            'tanggal_batas' => 'date:Y-m-d',
            'dikonfirmasi_pada' => 'datetime',
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
     * @return BelongsTo<SaldoCuti, $this>
     */
    public function saldoCuti(): BelongsTo
    {
        return $this->belongsTo(SaldoCuti::class);
    }

    /**
     * @return BelongsTo<Karyawan, $this>
     */
    public function dikonfirmasiOleh(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'dikonfirmasi_oleh_id');
    }
}
