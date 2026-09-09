<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\StatusKaryawan;
use App\Enums\TipeKaryawan;
use Database\Factories\KaryawanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nip
 * @property string $nama
 * @property string $email
 * @property string|null $no_hp
 * @property JenisKelamin $jenis_kelamin
 * @property int $departemen_id
 * @property int $jabatan_id
 * @property Carbon $tanggal_masuk
 * @property StatusKaryawan $status
 * @property TipeKaryawan $tipe_karyawan
 * @property Carbon|null $tanggal_akhir_kontrak
 */
#[Fillable([
    'nip',
    'nama',
    'email',
    'no_hp',
    'jenis_kelamin',
    'departemen_id',
    'jabatan_id',
    'tanggal_masuk',
    'status',
    'tipe_karyawan',
    'tanggal_akhir_kontrak',
])]
class Karyawan extends Model
{
    /** @use HasFactory<KaryawanFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'status' => StatusKaryawan::class,
            'jenis_kelamin' => JenisKelamin::class,
            'tipe_karyawan' => TipeKaryawan::class,
            'tanggal_akhir_kontrak' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Departemen, $this>
     */
    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class);
    }

    /**
     * @return BelongsTo<Jabatan, $this>
     */
    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * @return HasMany<PengajuanCuti, $this>
     */
    public function pengajuanCutis(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class);
    }

    /**
     * @return HasMany<SaldoCuti, $this>
     */
    public function saldoCutis(): HasMany
    {
        return $this->hasMany(SaldoCuti::class);
    }

    /**
     * @return HasMany<JadwalShift, $this>
     */
    public function jadwalShifts(): HasMany
    {
        return $this->hasMany(JadwalShift::class);
    }

    /**
     * Pengajuan cuti milik karyawan lain yang disetujui oleh karyawan ini.
     *
     * @return HasMany<Approval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'approver_id');
    }

    /**
     * @return HasMany<RiwayatSaldoCuti, $this>
     */
    public function riwayatSaldoCutis(): HasMany
    {
        return $this->hasMany(RiwayatSaldoCuti::class);
    }

    /**
     * @return HasMany<KompensasiCuti, $this>
     */
    public function kompensasiCutis(): HasMany
    {
        return $this->hasMany(KompensasiCuti::class);
    }

    /**
     * @return HasMany<KonfirmasiKontrakCuti, $this>
     */
    public function konfirmasiKontrakCutis(): HasMany
    {
        return $this->hasMany(KonfirmasiKontrakCuti::class);
    }
}
