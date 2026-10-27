<?php

namespace App\Models;

use App\Enums\PerangkatLogin;
use Database\Factories\RiwayatLoginFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property PerangkatLogin $perangkat
 * @property string|null $user_agent
 * @property string|null $ip_address
 */
#[Fillable([
    'user_id',
    'perangkat',
    'user_agent',
    'ip_address',
])]
class RiwayatLogin extends Model
{
    /** @use HasFactory<RiwayatLoginFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'perangkat' => PerangkatLogin::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
