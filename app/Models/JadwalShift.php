<?php

namespace App\Models;

use Database\Factories\JadwalShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $karyawan_id
 * @property int $shift_id
 * @property Carbon $tanggal
 * @property string|null $jam_lembur
 * @property string|null $catatan_lembur
 */
#[Fillable(['karyawan_id', 'shift_id', 'tanggal', 'jam_lembur', 'catatan_lembur'])]
class JadwalShift extends Model
{
    /** @use HasFactory<JadwalShiftFactory> */
    use HasFactory;

    /**
     * Simpan kolom tanggal sebagai "Y-m-d" murni (bukan default "Y-m-d H:i:s"
     * dari grammar koneksi), supaya query pencarian/where dengan string
     * tanggal polos (mis. updateOrCreate, whereBetween) cocok persis dengan
     * nilai yang tersimpan di database.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_lembur' => 'decimal:1',
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
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
