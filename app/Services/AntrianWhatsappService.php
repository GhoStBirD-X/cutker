<?php

namespace App\Services;

use App\Enums\StatusAntrianWhatsapp;
use App\Models\AntrianWhatsapp;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Throwable;

/**
 * Evolution API tidak menyimpan pesan yang gagal dikirim (mis. saat instance
 * disconnect), jadi setiap pesan dicatat di `antrian_whatsapps` dan yang gagal
 * dikirim ulang oleh `whatsapp:kirim-antrian` (scheduler tiap menit).
 */
class AntrianWhatsappService
{
    public const MAKSIMAL_PERCOBAAN = 10;

    protected const BATAS_PER_PROSES = 50;

    /**
     * Awalan teks semua toWhatsApp() di app/Notifications — membedakan notifikasi
     * cutker dari pesan tes manual di riwayat instance.
     */
    protected const POLA_NOTIFIKASI = '/^(Pengajuan Cuti |Update Pengajuan Cuti)/';

    /**
     * Kirim langsung sekali; kalau gagal, pesan tetap tersimpan sebagai pending.
     * Record baru dibuat setelah percobaan pertama selesai supaya scheduler tidak
     * mengirim pesan yang sedang dikirim request ini (pesan dobel).
     */
    public function kirim(string $nomor, string $pesan): AntrianWhatsapp
    {
        $nomor = $this->keNomorInternasional($nomor);
        $error = $this->kirimKeEvolution($nomor, $pesan);

        return AntrianWhatsapp::create([
            'nomor' => $nomor,
            'pesan' => $pesan,
            'status' => $error === null ? StatusAntrianWhatsapp::Terkirim : StatusAntrianWhatsapp::Pending,
            'percobaan' => 1,
            'error_terakhir' => $error,
            'terkirim_pada' => $error === null ? now() : null,
        ]);
    }

    /**
     * Kirim ulang pesan pending hanya saat instance tersambung, dengan jeda acak
     * 2–5 detik antar pesan agar nomor tidak dibatasi WhatsApp karena lonjakan.
     * Berhenti di tengah jalan kalau koneksi putus lagi.
     *
     * @return int|null Jumlah pesan yang berhasil terkirim, null kalau instance belum tersambung
     */
    public function prosesAntrian(): ?int
    {
        if (! $this->terhubung()) {
            return null;
        }

        $antrian = AntrianWhatsapp::query()
            ->where('status', StatusAntrianWhatsapp::Pending)
            ->orderBy('id')
            ->limit(self::BATAS_PER_PROSES)
            ->get();

        $terkirim = 0;

        foreach ($antrian as $urutan => $item) {
            if ($urutan > 0) {
                Sleep::for(random_int(2000, 5000))->milliseconds();
            }

            $error = $this->kirimKeEvolution($item->nomor, $item->pesan);
            $item->percobaan++;

            if ($error === null) {
                $item->fill([
                    'status' => StatusAntrianWhatsapp::Terkirim,
                    'error_terakhir' => null,
                    'terkirim_pada' => now(),
                ])->save();
                $terkirim++;

                continue;
            }

            $item->fill([
                'status' => $item->percobaan >= self::MAKSIMAL_PERCOBAAN
                    ? StatusAntrianWhatsapp::Gagal
                    : StatusAntrianWhatsapp::Pending,
                'error_terakhir' => $error,
            ])->save();

            if (! $this->terhubung()) {
                break;
            }
        }

        return $terkirim;
    }

    public function terhubung(): bool
    {
        try {
            $response = $this->http()->get($this->url('instance/connectionState'));
        } catch (Throwable) {
            return false;
        }

        return $response->successful() && $response->json('instance.state') === 'open';
    }

    /**
     * Notifikasi cutker yang ditolak WhatsApp (status ERROR, mis. ack 463) di suatu
     * instance. Penolakan ini datang setelah Evolution membalas HTTP 201, jadi di
     * antrian tercatat terkirim (atau tidak tercatat sama sekali kalau dikirim
     * sebelum ada antrian) — hanya riwayat pesan Evolution yang menyimpannya.
     *
     * @return Collection<int, array{nomor: string, pesan: string, waktu: Carbon}>
     */
    public function pesanDitolak(string $instance, ?Carbon $sejak = null): Collection
    {
        $records = [];
        $halaman = 1;

        do {
            $messages = $this->http()->timeout(30)
                ->post($this->url('chat/findMessages', $instance), [
                    'where' => ['key' => ['fromMe' => true]],
                    'page' => $halaman,
                    'offset' => 100,
                ])
                ->throw()
                ->json('messages');

            array_push($records, ...($messages['records'] ?? []));
            $halaman++;
        } while ($halaman <= ($messages['pages'] ?? 1));

        return collect($records)
            ->filter(fn (array $record) => ($record['status'] ?? null) === 'ERROR'
                || in_array('ERROR', array_column($record['MessageUpdate'] ?? [], 'status'), true))
            ->map(fn (array $record) => [
                'jid' => $record['key']['remoteJidAlt'] ?? $record['key']['remoteJid'] ?? '',
                'pesan' => $record['message']['conversation'] ?? $record['message']['extendedTextMessage']['text'] ?? '',
                'waktu' => Carbon::createFromTimestamp((int) ($record['messageTimestamp'] ?? 0), config('app.timezone')),
            ])
            ->filter(fn (array $item) => str_ends_with($item['jid'], '@s.whatsapp.net')
                && preg_match(self::POLA_NOTIFIKASI, $item['pesan'])
                && ($sejak === null || $item['waktu']->gte($sejak)))
            ->map(fn (array $item) => [
                'nomor' => strstr($item['jid'], '@', true),
                'pesan' => $item['pesan'],
                'waktu' => $item['waktu'],
            ])
            ->unique(fn (array $item) => $item['nomor'].'|'.$item['pesan'])
            ->sortBy('waktu')
            ->values();
    }

    /**
     * @return string|null Pesan error, atau null kalau berhasil terkirim
     */
    protected function kirimKeEvolution(string $nomor, string $pesan): ?string
    {
        try {
            $response = $this->http()->post($this->url('message/sendText'), [
                'number' => $nomor,
                'text' => $pesan,
            ]);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        if (isset($response) && $response->successful()) {
            return null;
        }

        $error ??= "HTTP {$response->status()}";
        Log::warning("Gagal mengirim WhatsApp ke {$nomor}: {$error}");

        return $error;
    }

    protected function http(): PendingRequest
    {
        return Http::timeout(5)->withHeaders(array_filter([
            'apikey' => config('services.evolution.api_key'),
        ]));
    }

    protected function url(string $endpoint, ?string $instance = null): string
    {
        $baseUrl = rtrim(config('services.evolution.base_url'), '/');
        $instance = rawurlencode($instance ?? config('services.evolution.instance'));

        return "{$baseUrl}/{$endpoint}/{$instance}";
    }

    protected function keNomorInternasional(string $nomor): string
    {
        $nomor = preg_replace('/\D/', '', $nomor);

        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }

        return $nomor;
    }
}
