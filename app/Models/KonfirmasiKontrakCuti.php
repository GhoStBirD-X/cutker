<?php

namespace App\Models;

use App\Enums\StatusKonfirmasiKontrak;
use Database\Factories\KonfirmasiKontrakCutiFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $karyawan_id
 * @property int $saldo_cuti_id
 * @property int $periode_ke
 * @property Carbon $tanggal_batas
 * @property StatusKonfirmasiKontrak $status
 * @property int|null $dikonfirmasi_oleh_id
 * @property Carbon|null $dikonfirmasi_pada
 * @property string|null $catatan
 */
#[Fillable([
    'karyawan_id',
    'saldo_cuti_id',
    'periode_ke',
    'tanggal_batas',
    'status',
    'dikonfirmasi_oleh_id',
    'dikonfirmasi_pada',
    'catatan',
])]
#[Appends(['urutan_kontrak'])]
class KonfirmasiKontrakCuti extends Model
{
    /** @use HasFactory<KonfirmasiKontrakCutiFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusKonfirmasiKontrak::class,
            // Format eksplisit "Y-m-d" (bukan default ISO datetime) supaya
            // nilai yang dikirim ke frontend bisa langsung dipakai sebagai
            // atribut "min" pada input tanggal HTML.
            'tanggal_batas' => 'date:Y-m-d',
            'dikonfirmasi_pada' => 'datetime',
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
     * @return BelongsTo<SaldoCuti, $this>
     */
    public function saldoCuti(): BelongsTo
    {
        return $this->belongsTo(SaldoCuti::class);
    }

    /**
     * @return BelongsTo<Karyawan, $this>
     */
    public function dikonfirmasiOleh(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'dikonfirmasi_oleh_id');
    }

    /**
     * Posisi kontrak yang berakhir (K1–K5), dipakai frontend untuk
     * menampilkan pilihan khusus di akhir K5.
     *
     * @return Attribute<int, never>
     */
    protected function urutanKontrak(): Attribute
    {
        return Attribute::get(fn (): int => SaldoCuti::urutanKontrakDariPeriode($this->periode_ke));
    }

    /**
     * Akhir K5: HRD harus memilih antara mengangkat karyawan menjadi tetap
     * atau kontrak ulang ke K1 (keadaan khusus, wajib beralasan) — tidak
     * bisa ikut perpanjangan massal.
     */
    public function diAkhirSiklusKontrak(): bool
    {
        return $this->urutan_kontrak === SaldoCuti::PANJANG_SIKLUS_KONTRAK;
    }
}
