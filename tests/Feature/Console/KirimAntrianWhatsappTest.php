<?php

namespace Tests\Feature\Console;

use App\Enums\StatusAntrianWhatsapp;
use App\Models\AntrianWhatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class KirimAntrianWhatsappTest extends TestCase
{
    use RefreshDatabase;

    protected const URL_STATUS = 'http://evolution.test/instance/connectionState/cutker';

    protected const URL_KIRIM = 'http://evolution.test/message/sendText/cutker';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.evolution.base_url' => 'http://evolution.test',
            'services.evolution.instance' => 'cutker',
        ]);

        Http::preventStrayRequests();
    }

    /**
     * @return array<string, mixed>
     */
    protected function statusKoneksi(string $state): array
    {
        return ['instance' => ['instanceName' => 'cutker', 'state' => $state]];
    }

    public function test_skips_the_queue_without_contacting_evolution_when_whatsapp_notifications_are_disabled(): void
    {
        config(['services.evolution.enabled' => false]);
        Http::fake();
        $antrian = AntrianWhatsapp::factory()->create();

        $this->artisan('whatsapp:kirim-antrian')
            ->expectsOutput('Notifikasi WhatsApp dimatikan (NOTIFIKASI_WHATSAPP=false), antrian tidak diproses.')
            ->assertExitCode(0);

        Http::assertNothingSent();
        $this->assertSame(StatusAntrianWhatsapp::Pending, $antrian->fresh()->status);
    }

    public function test_sends_pending_messages_with_a_pause_between_them_when_connected(): void
    {
        Sleep::fake();
        Http::fake([
            self::URL_STATUS => Http::response($this->statusKoneksi('open')),
            self::URL_KIRIM => Http::response(['status' => 'PENDING'], 201),
        ]);
        $pertama = AntrianWhatsapp::factory()->create(['nomor' => '6281111111111', 'pesan' => 'Pesan pertama']);
        $kedua = AntrianWhatsapp::factory()->create(['nomor' => '6282222222222', 'pesan' => 'Pesan kedua']);
        $sudahTerkirim = AntrianWhatsapp::factory()->terkirim()->create();

        $this->artisan('whatsapp:kirim-antrian')
            ->expectsOutput('2 pesan WhatsApp dari antrian berhasil terkirim.')
            ->assertExitCode(0);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->url() === self::URL_KIRIM
            && $request['number'] === '6281111111111'
            && $request['text'] === 'Pesan pertama');
        Http::assertSent(fn (Request $request) => $request->url() === self::URL_KIRIM
            && $request['number'] === '6282222222222'
            && $request['text'] === 'Pesan kedua');

        Sleep::assertSleptTimes(1);

        foreach ([$pertama, $kedua] as $antrian) {
            $antrian->refresh();
            $this->assertSame(StatusAntrianWhatsapp::Terkirim, $antrian->status);
            $this->assertSame(2, $antrian->percobaan);
            $this->assertNull($antrian->error_terakhir);
            $this->assertNotNull($antrian->terkirim_pada);
        }
        $this->assertSame(1, $sudahTerkirim->refresh()->percobaan);
    }

    public function test_does_not_send_anything_while_the_instance_is_disconnected(): void
    {
        Http::fake([
            self::URL_STATUS => Http::response($this->statusKoneksi('close')),
        ]);
        $antrian = AntrianWhatsapp::factory()->create();

        $this->artisan('whatsapp:kirim-antrian')
            ->expectsOutput('Instance WhatsApp belum tersambung, antrian tidak diproses.')
            ->assertExitCode(0);

        Http::assertNotSent(fn (Request $request) => $request->url() === self::URL_KIRIM);

        $antrian->refresh();
        $this->assertSame(StatusAntrianWhatsapp::Pending, $antrian->status);
        $this->assertSame(1, $antrian->percobaan);
    }

    public function test_keeps_a_failed_message_pending_and_stops_when_the_connection_drops_again(): void
    {
        Sleep::fake();
        Http::fake([
            self::URL_STATUS => Http::sequence()
                ->push($this->statusKoneksi('open'))
                ->push($this->statusKoneksi('close')),
            self::URL_KIRIM => Http::response(null, 500),
        ]);
        $gagal = AntrianWhatsapp::factory()->create(['error_terakhir' => null]);
        $belumDicoba = AntrianWhatsapp::factory()->create();

        $this->artisan('whatsapp:kirim-antrian')
            ->expectsOutput('0 pesan WhatsApp dari antrian berhasil terkirim.')
            ->assertExitCode(0);

        Http::assertSentCount(3);

        $gagal->refresh();
        $this->assertSame(StatusAntrianWhatsapp::Pending, $gagal->status);
        $this->assertSame(2, $gagal->percobaan);
        $this->assertSame('HTTP 500', $gagal->error_terakhir);

        $this->assertSame(1, $belumDicoba->refresh()->percobaan);
    }

    public function test_marks_a_message_as_failed_after_the_maximum_number_of_attempts(): void
    {
        Http::fake([
            self::URL_STATUS => Http::response($this->statusKoneksi('open')),
            self::URL_KIRIM => Http::response(null, 400),
        ]);
        $antrian = AntrianWhatsapp::factory()->create(['percobaan' => 9]);

        $this->artisan('whatsapp:kirim-antrian')->assertExitCode(0);

        $antrian->refresh();
        $this->assertSame(StatusAntrianWhatsapp::Gagal, $antrian->status);
        $this->assertSame(10, $antrian->percobaan);
        $this->assertSame('HTTP 400', $antrian->error_terakhir);
    }
}
