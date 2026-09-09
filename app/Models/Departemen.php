<?php

namespace App\Models;

use Database\Factories\DepartemenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_departemen', 'kode'])]
class Departemen extends Model
{
    /** @use HasFactory<DepartemenFactory> */
    use HasFactory;

    /**
     * @return HasMany<Karyawan, $this>
     */
    public function karyawans(): HasMany
    {
        return $this->hasMany(Karyawan::class);
    }
}
