<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use Database\Factories\PengajuanCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $karyawan_id
 * @property int $jenis_cuti_id
 * @property int|null $alasan_cuti_id
 * @property Carbon $tanggal_mulai
 * @property Carbon $tanggal_selesai
 * @property int $jumlah_hari
 * @property int $jumlah_hari_kalender
 * @property string $alasan
 * @property StatusPengajuan $status
 * @property Carbon $tanggal_pengajuan
 * @property string|null $lampiran
 */
#[Fillable([
    'karyawan_id',
    'jenis_cuti_id',
    'alasan_cuti_id',
    'tanggal_mulai',
    'tanggal_selesai',
    'jumlah_hari',
    'jumlah_hari_kalender',
    'alasan',
    'status',
    'tanggal_pengajuan',
    'lampiran',
])]
class PengajuanCuti extends Model
{
    /** @use HasFactory<PengajuanCutiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tanggal_pengajuan' => 'datetime',
            'status' => StatusPengajuan::class,
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
     * @return BelongsTo<AlasanCuti, $this>
     */
    public function alasanCuti(): BelongsTo
    {
        return $this->belongsTo(AlasanCuti::class);
    }

    /**
     * @return HasMany<Approval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }
}
