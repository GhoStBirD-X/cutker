<?php

namespace Tests\Feature\Notifications;

use App\Enums\StatusAntrianWhatsapp;
use App\Models\AntrianWhatsapp;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Notifications\PengajuanCutiDiajukan;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class PengajuanCutiWhatsAppTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config([
            'services.evolution.base_url' => 'http://evolution.test',
            'services.evolution.instance' => 'cutker',
        ]);
    }

    /**
     * @return array{0: Karyawan, 1: JenisCuti}
     */
    protected function ajukanCuti(string $noHpKepalaBagian = '081234567890', ?UploadedFile $lampiran = null): array
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
            'lampiran' => $lampiran,
        ]);

        return [$karyawan, $jenisCuti];
    }

    public function test_kepala_bagian_receives_whatsapp_message_when_leave_is_submitted(): void
    {
        Http::fake(['*/message/sendText/*' => Http::response(['status' => 'PENDING'], 201)]);

        [$karyawan] = $this->ajukanCuti(noHpKepalaBagian: '081234567890');

        Http::assertSent(function (Request $request) use ($karyawan) {
            return $request->url() === 'http://evolution.test/message/sendText/cutker'
                && $request['number'] === '6281234567890'
                && str_contains($request['text'], "*{$karyawan->nama}*")
                && str_contains($request['text'], 'Acara keluarga')
                && str_contains($request['text'], url('/approval'));
        });
    }

    public function test_leave_request_still_succeeds_when_whatsapp_gateway_is_unreachable(): void
    {
        Http::fake(['*/message/sendText/*' => Http::response(null, 500)]);

        $this->ajukanCuti();

        $this->assertDatabaseCount('pengajuan_cutis', 1);
        $this->assertDatabaseCount('approvals', 1);
    }

    public function test_message_is_queued_for_resending_when_whatsapp_gateway_fails(): void
    {
        Http::fake(['*/message/sendText/*' => Http::response(null, 500)]);

        [$karyawan] = $this->ajukanCuti(noHpKepalaBagian: '081234567890');

        $antrian = AntrianWhatsapp::sole();
        $this->assertSame('6281234567890', $antrian->nomor);
        $this->assertStringContainsString("*{$karyawan->nama}*", $antrian->pesan);
        $this->assertSame(StatusAntrianWhatsapp::Pending, $antrian->status);
        $this->assertSame(1, $antrian->percobaan);
        $this->assertSame('HTTP 500', $antrian->error_terakhir);
    }

    public function test_successfully_sent_message_is_not_left_pending(): void
    {
        Http::fake(['*/message/sendText/*' => Http::response(['status' => 'PENDING'], 201)]);

        $this->ajukanCuti();

        $antrian = AntrianWhatsapp::sole();
        $this->assertSame(StatusAntrianWhatsapp::Terkirim, $antrian->status);
        $this->assertNotNull($antrian->terkirim_pada);
    }

    public function test_no_whatsapp_is_sent_or_queued_when_whatsapp_notifications_are_disabled(): void
    {
        config(['services.evolution.enabled' => false]);
        Http::fake();

        $this->ajukanCuti();

        Http::assertNothingSent();
        $this->assertDatabaseCount('antrian_whatsapps', 0);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_no_whatsapp_is_sent_when_kepala_bagian_has_no_phone_number(): void
    {
        Http::fake();

        $this->ajukanCuti(noHpKepalaBagian: '');

        Http::assertNothingSent();
    }

    public function test_whatsapp_message_mentions_attachment_when_leave_has_one(): void
    {
        Storage::fake('public');
        Http::fake(['*/message/sendText/*' => Http::response(['status' => 'PENDING'], 201)]);

        $this->ajukanCuti(lampiran: UploadedFile::fake()->create('surat-dokter.pdf', 100, 'application/pdf'));

        Http::assertSent(fn (Request $request) => str_contains($request['text'], PengajuanCutiDiajukan::KETERANGAN_LAMPIRAN));
    }

    public function test_whatsapp_message_does_not_mention_attachment_when_leave_has_none(): void
    {
        Http::fake(['*/message/sendText/*' => Http::response(['status' => 'PENDING'], 201)]);

        $this->ajukanCuti();

        Http::assertSent(fn (Request $request) => ! str_contains($request['text'], 'Lampiran'));
    }

    public function test_email_mentions_attachment_when_leave_has_one(): void
    {
        Storage::fake('public');
        Http::fake(['*/message/sendText/*' => Http::response(['status' => 'PENDING'], 201)]);

        [$karyawan] = $this->ajukanCuti(lampiran: UploadedFile::fake()->create('surat-dokter.pdf', 100, 'application/pdf'));

        $mail = (new PengajuanCutiDiajukan(PengajuanCuti::sole()))->toMail($karyawan);

        $this->assertContains(PengajuanCutiDiajukan::KETERANGAN_LAMPIRAN, $mail->introLines);
    }
}
