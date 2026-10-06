<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sengaja tidak melempar exception sama sekali (lihat .ai/rules/notifications.md)
 * — Evolution API down/timeout tidak boleh menggagalkan pengajuan cuti atau channel
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
                    'apikey' => config('services.evolution.api_key'),
                ]))
                ->post($this->urlSendText(), [
                    'number' => $this->keNomorInternasional($nomor),
                    'text' => $pesan,
                ]);

            if (! $response->successful()) {
                Log::warning("Gagal mengirim WhatsApp ke {$nomor}: HTTP {$response->status()}");
            }
        } catch (Throwable $e) {
            Log::warning("Gagal mengirim WhatsApp ke {$nomor}: {$e->getMessage()}");
        }
    }

    protected function urlSendText(): string
    {
        $baseUrl = rtrim(config('services.evolution.base_url'), '/');
        $instance = rawurlencode(config('services.evolution.instance'));

        return "{$baseUrl}/message/sendText/{$instance}";
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
