<?php

namespace App\Models;

use App\Enums\SumberHariLibur;
use Database\Factories\HariLiburFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tanggal', 'keterangan', 'sumber'])]
class HariLibur extends Model
{
    /** @use HasFactory<HariLiburFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Format eksplisit Y-m-d (bukan default Y-m-d H:i:s milik cast
            // 'date' biasa) supaya nilai yang tersimpan cocok persis dengan
            // string tanggal yang dipakai Rule::unique & updateOrCreate di
            // HariLiburService::sync() — keduanya mencocokkan dengan '='.
            'tanggal' => 'date:Y-m-d',
            'sumber' => SumberHariLibur::class,
        ];
    }
}
