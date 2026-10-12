<?php

namespace Tests\Feature\Console;

use App\Enums\StatusAntrianWhatsapp;
use App\Models\AntrianWhatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KirimUlangWhatsappDitolakTest extends TestCase
{
    use RefreshDatabase;

    protected const URL_RIWAYAT = 'http://evolution.test/chat/findMessages/KKP';

    protected const NOTIFIKASI = "Pengajuan Cuti Baru\n\nKaryawan: *Slamet*";

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.evolution.base_url' => 'http://evolution.test',
            'services.evolution.instance' => 'KKP2',
            'app.timezone' => 'UTC',
        ]);

        Http::preventStrayRequests();
    }

    /**
     * @return array<string, mixed>
     */
    protected function pesanKeluar(string $jid, string $teks, string $status, string $waktu = '2026-10-12 08:00:00'): array
    {
        return [
            'key' => ['remoteJid' => $jid, 'fromMe' => true, 'id' => uniqid()],
            'message' => ['conversation' => $teks],
            'messageTimestamp' => strtotime($waktu.' UTC'),
            'status' => $status,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    protected function halaman(array $records, int $pages): array
    {
        return ['messages' => ['total' => count($records), 'pages' => $pages, 'currentPage' => 1, 'records' => $records]];
    }

    protected function fakeRiwayat(): void
    {
        Http::fake([
            self::URL_RIWAYAT => Http::sequence()
                ->push($this->halaman([
                    $this->pesanKeluar('6281111111111@s.whatsapp.net', self::NOTIFIKASI, 'ERROR'),
                    $this->pesanKeluar('6281111111111@s.whatsapp.net', 'tes dari curl', 'ERROR'),
                    $this->pesanKeluar('6282222222222@s.whatsapp.net', self::NOTIFIKASI, 'DELIVERY_ACK'),
                ], pages: 2))
                ->push($this->halaman([
                    $this->pesanKeluar('6283333333333@s.whatsapp.net', "Update Pengajuan Cuti\n\nDisetujui", 'ERROR'),
                    $this->pesanKeluar('6281111111111@s.whatsapp.net', self::NOTIFIKASI, 'ERROR'),
                    $this->pesanKeluar('6284444444444@s.whatsapp.net', self::NOTIFIKASI, 'ERROR', '2026-10-11 23:59:00'),
                    $this->pesanKeluar('123456789@lid', self::NOTIFIKASI, 'ERROR'),
                ], pages: 2)),
        ]);
    }

    public function test_lists_rejected_notifications_without_queueing_them_by_default(): void
    {
        $this->fakeRiwayat();

        $this->artisan('whatsapp:kirim-ulang-ditolak', ['instance' => 'KKP', '--sejak' => '2026-10-12'])
            ->expectsOutputToContain('2 pesan ditemukan')
            ->assertExitCode(0);

        Http::assertSent(fn (Request $request) => $request->url() === self::URL_RIWAYAT && $request['page'] === 2);
        $this->assertDatabaseCount('antrian_whatsapps', 0);
    }

    public function test_reports_when_the_instance_has_no_rejected_notifications(): void
    {
        Http::fake([
            self::URL_RIWAYAT => Http::response($this->halaman([
                $this->pesanKeluar('6281111111111@s.whatsapp.net', self::NOTIFIKASI, 'DELIVERY_ACK'),
            ], pages: 1)),
        ]);

        $this->artisan('whatsapp:kirim-ulang-ditolak', ['instance' => 'KKP', '--simpan' => true])
            ->expectsOutput('Tidak ada notifikasi yang ditolak di instance KKP.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('antrian_whatsapps', 0);
    }

    public function test_queues_only_rejected_cutker_notifications_once_each_with_simpan(): void
    {
        $this->fakeRiwayat();

        $this->artisan('whatsapp:kirim-ulang-ditolak', ['instance' => 'KKP', '--sejak' => '2026-10-12', '--simpan' => true])
            ->expectsOutput('2 pesan masuk antrian dan akan dikirim lewat instance KKP2.')
            ->assertExitCode(0);

        $antrian = AntrianWhatsapp::orderBy('id')->get();
        $this->assertSame(['6281111111111', '6283333333333'], $antrian->pluck('nomor')->all());
        $this->assertSame([self::NOTIFIKASI, "Update Pengajuan Cuti\n\nDisetujui"], $antrian->pluck('pesan')->all());
        $this->assertSame([StatusAntrianWhatsapp::Pending, StatusAntrianWhatsapp::Pending], $antrian->pluck('status')->all());
    }
}
