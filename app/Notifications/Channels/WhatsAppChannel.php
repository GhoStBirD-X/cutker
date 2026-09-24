<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sengaja tidak melempar exception sama sekali (lihat .ai/rules/notifications.md)
 * — WAHA down/timeout tidak boleh menggagalkan pengajuan cuti atau channel
 * notifikasi lain (database/mail).
 */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $nomor = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! $nomor) {
            return;
        }

        $pesan = $notification->toWhatsApp($notifiable);

        try {
            $response = Http::timeout(5)
                ->withHeaders(array_filter([
                    'X-Api-Key' => config('services.waha.api_key'),
                ]))
                ->post(rtrim(config('services.waha.base_url'), '/').'/api/sendText', [
                    'session' => config('services.waha.session'),
                    'chatId' => $this->keChatId($nomor),
                    'text' => $pesan,
                ]);

            if (! $response->successful()) {
                Log::warning("Gagal mengirim WhatsApp ke {$nomor}: HTTP {$response->status()}");
            }
        } catch (Throwable $e) {
            Log::warning("Gagal mengirim WhatsApp ke {$nomor}: {$e->getMessage()}");
        }
    }

    protected function keChatId(string $nomor): string
    {
        $nomor = preg_replace('/\D/', '', $nomor);

        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }

        return "{$nomor}@c.us";
    }
}
