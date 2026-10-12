<?php

namespace App\Console\Commands;

use App\Enums\StatusAntrianWhatsapp;
use App\Models\AntrianWhatsapp;
use App\Services\AntrianWhatsappService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('whatsapp:kirim-ulang-ditolak
    {instance : Nama instance Evolution tempat pesan ditolak (mis. instance lama yang dibatasi WhatsApp)}
    {--sejak= : Hanya pesan sejak tanggal ini (Y-m-d)}
    {--simpan : Masukkan ke antrian; tanpa opsi ini hanya menampilkan daftar}')]
#[Description('Masukkan ulang notifikasi WhatsApp yang ditolak WhatsApp (status ERROR) di suatu instance ke antrian')]
class KirimUlangWhatsappDitolak extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AntrianWhatsappService $antrianWhatsappService): int
    {
        $instance = $this->argument('instance');
        $sejak = $this->option('sejak') ? Carbon::parse($this->option('sejak'))->startOfDay() : null;

        $pesanDitolak = $antrianWhatsappService->pesanDitolak($instance, $sejak);

        if ($pesanDitolak->isEmpty()) {
            $this->info("Tidak ada notifikasi yang ditolak di instance {$instance}.");

            return self::SUCCESS;
        }

        $this->table(['Waktu', 'Nomor', 'Pesan'], $pesanDitolak->map(fn (array $item) => [
            $item['waktu']->toDateTimeString(),
            $item['nomor'],
            strtok($item['pesan'], "\n"),
        ]));

        if (! $this->option('simpan')) {
            $this->warn("{$pesanDitolak->count()} pesan ditemukan. Jalankan lagi dengan --simpan untuk memasukkan ke antrian.");

            return self::SUCCESS;
        }

        foreach ($pesanDitolak as $item) {
            AntrianWhatsapp::create([
                'nomor' => $item['nomor'],
                'pesan' => $item['pesan'],
                'status' => StatusAntrianWhatsapp::Pending,
                'percobaan' => 1,
                'error_terakhir' => "Ditolak WhatsApp di instance {$instance}",
            ]);
        }

        $this->info("{$pesanDitolak->count()} pesan masuk antrian dan akan dikirim lewat instance ".config('services.evolution.instance').'.');

        return self::SUCCESS;
    }
}
