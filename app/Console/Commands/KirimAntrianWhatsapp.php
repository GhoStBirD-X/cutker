<?php

namespace App\Console\Commands;

use App\Services\AntrianWhatsappService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('whatsapp:kirim-antrian')]
#[Description('Kirim ulang pesan WhatsApp yang tertunda saat instance Evolution API terputus')]
class KirimAntrianWhatsapp extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AntrianWhatsappService $antrianWhatsappService): int
    {
        if (! config('services.evolution.enabled')) {
            $this->info('Notifikasi WhatsApp dimatikan (NOTIFIKASI_WHATSAPP=false), antrian tidak diproses.');

            return self::SUCCESS;
        }

        $terkirim = $antrianWhatsappService->prosesAntrian();

        if ($terkirim === null) {
            $this->warn('Instance WhatsApp belum tersambung, antrian tidak diproses.');

            return self::SUCCESS;
        }

        $this->info("{$terkirim} pesan WhatsApp dari antrian berhasil terkirim.");

        return self::SUCCESS;
    }
}
