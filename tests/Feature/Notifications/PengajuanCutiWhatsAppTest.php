<?php

namespace Tests\Feature\Notifications;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class PengajuanCutiWhatsAppTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: Karyawan, 1: JenisCuti}
     */
    protected function ajukanCuti(string $noHpKepalaBagian = '081234567890'): array
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', [
            'departemen_id' => $karyawan->departemen_id,
            'no_hp' => $noHpKepalaBagian,
        ]);

        $jenisCuti = JenisCuti::factory()->create();
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'alasan' => 'Acara keluarga',
        ]);

        return [$karyawan, $jenisCuti];
    }

    public function test_kepala_bagian_receives_whatsapp_message_when_leave_is_submitted(): void
    {
        Http::fake(['*/api/sendText' => Http::response(['status' => 'sent'], 200)]);

        [$karyawan] = $this->ajukanCuti(noHpKepalaBagian: '081234567890');

        Http::assertSent(function (Request $request) use ($karyawan) {
            return $request->url() === 'http://localhost:3000/api/sendText'
                && $request['chatId'] === '6281234567890@c.us'
                && str_contains($request['text'], "*{$karyawan->nama}*")
                && str_contains($request['text'], 'Acara keluarga')
                && str_contains($request['text'], url('/approval'));
        });
    }

    public function test_leave_request_still_succeeds_when_whatsapp_gateway_is_unreachable(): void
    {
        Http::fake(['*/api/sendText' => Http::response(null, 500)]);

        $this->ajukanCuti();

        $this->assertDatabaseCount('pengajuan_cutis', 1);
        $this->assertDatabaseCount('approvals', 1);
    }

    public function test_no_whatsapp_is_sent_when_kepala_bagian_has_no_phone_number(): void
    {
        Http::fake();

        $this->ajukanCuti(noHpKepalaBagian: '');

        Http::assertNothingSent();
    }
}
