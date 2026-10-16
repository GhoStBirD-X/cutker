<?php

namespace Tests\Feature;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use App\Support\SaldoSeverity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaldoCutiScopeKritisTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_kritis_matches_saldo_severity_critical(): void
    {
        $kombinasi = [[3, 12], [0, 12], [-1, 12], [4, 12], [12, 12], [0, 0], [5, 0], [5, 22], [6, 22], [null, null], [5, null]];

        $karyawan = Karyawan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create();

        foreach ($kombinasi as $indeks => [$sisa, $kuota]) {
            SaldoCuti::factory()->create([
                'karyawan_id' => $karyawan->id,
                'jenis_cuti_id' => $jenisCuti->id,
                'periode_ke' => $indeks + 1,
                'sisa' => $sisa,
                'kuota' => $kuota,
            ]);
        }

        $harapan = SaldoCuti::all()
            ->filter(fn (SaldoCuti $saldo) => SaldoSeverity::hitung($saldo->sisa, $saldo->kuota) === SaldoSeverity::CRITICAL)
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame($harapan, SaldoCuti::query()->kritis()->orderBy('id')->pluck('id')->all());
        $this->assertCount(5, $harapan);
    }
}
