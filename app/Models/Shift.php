<?php

namespace App\Models;

use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nama_shift
 * @property Carbon $jam_mulai
 * @property Carbon $jam_selesai
 */
#[Fillable(['nama_shift', 'jam_mulai', 'jam_selesai'])]
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jam_mulai' => 'datetime:H:i',
            'jam_selesai' => 'datetime:H:i',
        ];
    }

    /**
     * @return HasMany<JadwalShift, $this>
     */
    public function jadwalShifts(): HasMany
    {
        return $this->hasMany(JadwalShift::class);
    }
}
