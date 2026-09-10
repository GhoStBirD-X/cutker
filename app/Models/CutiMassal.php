<?php

namespace App\Models;

use App\Enums\StatusCutiMassal;
use Database\Factories\CutiMassalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $jenis_cuti_id
 * @property Carbon $tanggal_mulai
 * @property Carbon $tanggal_selesai
 * @property int $jumlah_hari
 * @property int $jumlah_hari_kalender
 * @property string $alasan
 * @property int $dibuat_oleh_id
 * @property int $jumlah_karyawan
 * @property array<int, array{karyawan_id: int, nama: string, alasan: string}>|null $dilewati
 * @property StatusCutiMassal $status
 * @property int|null $dibatalkan_oleh_id
 * @property Carbon|null $dibatalkan_pada
 * @property string|null $catatan_pembatalan
 */
#[Fillable([
    'jenis_cuti_id',
    'tanggal_mulai',
    'tanggal_selesai',
    'jumlah_hari',
    'jumlah_hari_kalender',
    'alasan',
    'dibuat_oleh_id',
    'jumlah_karyawan',
    'dilewati',
    'status',
    'dibatalkan_oleh_id',
    'dibatalkan_pada',
    'catatan_pembatalan',
])]
class CutiMassal extends Model
{
    /** @use HasFactory<CutiMassalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'dilewati' => 'array',
            'status' => StatusCutiMassal::class,
            'dibatalkan_pada' => 'datetime',
        ];
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
    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'dibuat_oleh_id');
    }

    /**
     * @return BelongsTo<Karyawan, $this>
     */
    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'dibatalkan_oleh_id');
    }

    /**
     * @return HasMany<PengajuanCuti, $this>
     */
    public function pengajuanCutis(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class);
    }
}
