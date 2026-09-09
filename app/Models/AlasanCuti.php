<?php

namespace App\Models;

use Database\Factories\AlasanCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $jenis_cuti_id
 * @property string $nama_alasan
 * @property int|null $jumlah_hari
 * @property string|null $keterangan
 */
#[Fillable(['jenis_cuti_id', 'nama_alasan', 'jumlah_hari', 'keterangan'])]
class AlasanCuti extends Model
{
    /** @use HasFactory<AlasanCutiFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<JenisCuti, $this>
     */
    public function jenisCuti(): BelongsTo
    {
        return $this->belongsTo(JenisCuti::class);
    }

    /**
     * @return HasMany<PengajuanCuti, $this>
     */
    public function pengajuanCutis(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class);
    }
}
