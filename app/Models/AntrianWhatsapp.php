<?php

namespace App\Models;

use App\Enums\StatusAntrianWhatsapp;
use Database\Factories\AntrianWhatsappFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nomor
 * @property string $pesan
 * @property StatusAntrianWhatsapp $status
 * @property int $percobaan
 * @property string|null $error_terakhir
 * @property Carbon|null $terkirim_pada
 */
#[Fillable([
    'nomor',
    'pesan',
    'status',
    'percobaan',
    'error_terakhir',
    'terkirim_pada',
])]
class AntrianWhatsapp extends Model
{
    /** @use HasFactory<AntrianWhatsappFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusAntrianWhatsapp::class,
            'terkirim_pada' => 'datetime',
        ];
    }
}
