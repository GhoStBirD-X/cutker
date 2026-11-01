<?php

namespace App\Notifications\Channels;

use App\Services\AntrianWhatsappService;
use Illuminate\Notifications\Notification;

/**
 * Sengaja tidak melempar exception saat Evolution API down/timeout (lihat
 * .ai/rules/notifications.md) — tidak boleh menggagalkan pengajuan cuti atau
 * channel notifikasi lain (database/mail). Pesan yang gagal masuk antrian dan
 * dikirim ulang oleh scheduler, lihat AntrianWhatsappService. Notifikasi dengan
 * properti `lewatAntrianWhatsapp = true` (kiriman massal) tidak dikirim langsung,
 * hanya diantrikan supaya dicicil scheduler dengan jeda antar pesan.
 *
 * Seluruh channel dimatikan lewat NOTIFIKASI_WHATSAPP=false untuk server tanpa
 * Evolution API (mis. hosting cPanel) — notifikasi hanya lewat database + email.
 */
class WhatsAppChannel
{
    public function __construct(protected AntrianWhatsappService $antrianWhatsapp) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! config('services.evolution.enabled') || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $nomor = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! $nomor) {
            return;
        }

        $pesan = $notification->toWhatsApp($notifiable);

        if ($notification->lewatAntrianWhatsapp ?? false) {
            $this->antrianWhatsapp->antrikan($nomor, $pesan);

            return;
        }

        $this->antrianWhatsapp->kirim($nomor, $pesan);
    }
}
